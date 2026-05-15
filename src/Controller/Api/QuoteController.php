<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Config\PackageSlug;
use App\Dto\QuoteRequest;
use App\Entity\Quote;
use App\Repository\PackageRepository;
use App\Repository\QuoteRepository;
use App\Service\MailerService;
use App\Service\RequestPayloadDeserializer;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Exception\ApiErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/quotes', name: 'api_quotes_')]
final class QuoteController extends AbstractController
{
    public function __construct(
        private readonly RequestPayloadDeserializer $deserializer,
        private readonly EntityManagerInterface $em,
        private readonly PackageRepository $packages,
        private readonly QuoteRepository $quotes,
        private readonly StripeService $stripe,
        private readonly MailerService $mailer,
        private readonly RateLimiterFactory $quoteLimiter,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $limit = $this->quoteLimiter->create($request->getClientIp() ?? 'anon')->consume();
        if (!$limit->isAccepted()) {
            return $this->json(['error' => 'Trop de demandes en peu de temps. Réessayez plus tard.'], 429);
        }

        [$dto, $errors] = $this->deserializer->deserializeAndValidate($request, QuoteRequest::class);
        if ($errors !== []) {
            return $this->json(['error' => 'Champs invalides.', 'fields' => $errors], 400);
        }
        \assert($dto instanceof QuoteRequest);

        $package = $dto->package !== null ? $this->packages->findOneBySlug($dto->package) : null;
        if ($package === null || !$package->isActive()) {
            return $this->json(['error' => 'Forfait introuvable.'], 404);
        }

        $quote = new Quote(
            package: $package,
            clientName: $dto->clientName,
            clientEmail: $dto->clientEmail,
            clientPhone: $dto->clientPhone,
            projectDescription: $dto->projectDescription,
        );
        $quote->setClientCompany($dto->clientCompany);
        $quote->setClientAddress($dto->clientAddress);
        $quote->setOptions($dto->options);
        $quote->setIp($request->getClientIp());
        $quote->setLocale($dto->locale);

        $this->em->persist($quote);
        $this->em->flush();

        try {
            $session = $this->stripe->createCheckoutSession($quote);
            $quote->setStripeCustomerId($session->customer ? (string) $session->customer : null);
            $quote->setStripeCheckoutSessionId($session->id);
            $this->em->flush();

            $checkoutUrl = $session->url ?? '';
        } catch (ApiErrorException | \RuntimeException $e) {
            return $this->json(['error' => 'Initialisation Stripe impossible : ' . $e->getMessage()], 502);
        }

        try {
            $this->mailer->sendQuoteClientReceipt($quote);
            $this->mailer->sendQuoteAdminNotification($quote);
        } catch (\Throwable) {
            // Persistance OK, mail facultatif.
        }

        return $this->json([
            'id' => (string) $quote->getId(),
            'checkoutUrl' => $checkoutUrl,
            'totalOneShot' => $quote->getTotalOneShotCents(),
            'totalMonthly' => $quote->getTotalMonthlyCents(),
        ], 201);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function show(string $id): JsonResponse
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\Throwable) {
            return $this->json(['error' => 'Identifiant invalide.'], 400);
        }

        $quote = $this->quotes->findById($uuid);
        if ($quote === null) {
            return $this->json(['error' => 'Devis introuvable.'], 404);
        }

        return $this->json([
            'id' => (string) $quote->getId(),
            'package' => $quote->getPackage()->getSlug()->value,
            'clientName' => $quote->getClientName(),
            'clientEmail' => $quote->getClientEmail(),
            'status' => $quote->getStatus()->value,
            'totalOneShot' => $quote->getTotalOneShotCents(),
            'totalMonthly' => $quote->getTotalMonthlyCents(),
            'createdAt' => $quote->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }
}
