<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\QuoteStatus;
use App\Repository\QuoteRepository;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class StripeWebhookController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepository $quotes,
        private readonly EntityManagerInterface $em,
        private readonly StripeService $stripe,
        private readonly LoggerInterface $logger,
        private readonly string $stripeWebhookSecret,
    ) {
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->headers->get('Stripe-Signature') ?? '';

        try {
            $event = Webhook::constructEvent($payload, $signature, $this->stripeWebhookSecret);
        } catch (SignatureVerificationException $e) {
            $this->logger->warning('Stripe signature invalid', ['error' => $e->getMessage()]);
            return $this->json(['error' => 'Signature invalide.'], 400);
        } catch (\UnexpectedValueException $e) {
            return $this->json(['error' => 'Payload invalide.'], 400);
        }

        match ($event->type) {
            'checkout.session.completed' => $this->onCheckoutCompleted($event),
            'customer.subscription.created' => $this->onSubscriptionCreated($event),
            'invoice.payment_failed' => $this->onInvoiceFailed($event),
            default => null,
        };

        return $this->json(['received' => true]);
    }

    private function onCheckoutCompleted(Event $event): void
    {
        $session = $event->data->object;
        $quote = $this->quotes->findByCheckoutSession((string) $session->id);
        if ($quote === null) {
            return;
        }

        $quote->setStatus(QuoteStatus::DepositPaid);
        if (isset($session->subscription) && is_string($session->subscription)) {
            $quote->setStripeSubscriptionId($session->subscription);
        }
        if (isset($session->customer) && is_string($session->customer)) {
            $quote->setStripeCustomerId($session->customer);
        }

        $depositCents = isset($session->metadata['deposit_cents']) ? (int) $session->metadata['deposit_cents'] : 0;
        $packageName = $session->metadata['package'] ?? 'site web';

        if ($depositCents > 0 && $quote->getStripeCustomerId() && $quote->getStripeSubscriptionId()) {
            try {
                $this->stripe->addDepositInvoiceItem(
                    $quote->getStripeCustomerId(),
                    $quote->getStripeSubscriptionId(),
                    $depositCents,
                    $packageName,
                );
            } catch (\Throwable $e) {
                $this->logger->error('Failed to attach deposit invoice item', [
                    'quote' => (string) $quote->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->em->flush();
    }

    private function onSubscriptionCreated(Event $event): void
    {
        $sub = $event->data->object;
        $quoteId = $sub->metadata['quote_id'] ?? null;
        if (!is_string($quoteId)) {
            return;
        }

        try {
            $uuid = Uuid::fromString($quoteId);
        } catch (\Throwable) {
            return;
        }

        $quote = $this->quotes->findById($uuid);
        if ($quote === null) {
            return;
        }

        $quote->setStripeSubscriptionId((string) $sub->id);
        if ($quote->getStatus() === QuoteStatus::DepositPaid) {
            $quote->setStatus(QuoteStatus::Active);
        }
        $this->em->flush();
    }

    private function onInvoiceFailed(Event $event): void
    {
        $invoice = $event->data->object;
        $subId = is_string($invoice->subscription ?? null) ? $invoice->subscription : null;
        if ($subId === null) {
            return;
        }
        $quote = $this->quotes->findBySubscription($subId);
        if ($quote === null) {
            return;
        }
        $this->logger->warning('Invoice payment failed', [
            'quote' => (string) $quote->getId(),
            'invoice' => $invoice->id ?? null,
        ]);
    }
}
