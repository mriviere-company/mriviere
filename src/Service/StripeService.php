<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Quote;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\StripeClient;

final readonly class StripeService
{
    private StripeClient $client;

    public function __construct(
        string $stripeSecretKey,
        private string $publicBaseUrl,
    ) {
        $this->client = new StripeClient($stripeSecretKey);
    }

    /**
     * Create a Stripe Checkout Session in `subscription` mode that includes the deposit
     * (50 % of the one-shot creation fee) as a one-time invoice item attached to the first
     * subscription invoice. Uses the official "subscription with one-time setup fees" pattern.
     *
     * @param Quote $quote The quote to bill — must have a `Package` with `stripeMonthlyPriceId` set.
     */
    public function createCheckoutSession(Quote $quote): CheckoutSession
    {
        $monthlyPriceId = $quote->getPackage()->getStripeMonthlyPriceId();
        if ($monthlyPriceId === null || $monthlyPriceId === '') {
            throw new \RuntimeException(sprintf('Package %s has no Stripe monthly price configured.', $quote->getPackage()->getSlug()->value));
        }

        $depositCents = (int) round($quote->getTotalOneShotCents() / 2);

        return $this->client->checkout->sessions->create([
            'mode' => 'subscription',
            'customer_email' => $quote->getClientEmail(),
            'client_reference_id' => (string) $quote->getId(),
            'line_items' => [
                [
                    'price' => $monthlyPriceId,
                    'quantity' => 1,
                ],
            ],
            'subscription_data' => [
                'metadata' => [
                    'quote_id' => (string) $quote->getId(),
                    'package' => $quote->getPackage()->getSlug()->value,
                ],
            ],
            'payment_method_collection' => 'always',
            'metadata' => [
                'quote_id' => (string) $quote->getId(),
                'deposit_cents' => (string) $depositCents,
                'package' => $quote->getPackage()->getSlug()->value,
            ],
            'invoice_creation' => null,
            // Pass the locale through so the confirmation page boots in the language
            // the customer used at checkout — Stripe-redirected sessions arrive in a
            // fresh tab without our localStorage, so we need an explicit hint.
            'success_url' => $this->publicBaseUrl . '/devis/confirmation/' . $quote->getId() . '?locale=' . $quote->getLocale(),
            'cancel_url' => $this->publicBaseUrl . '/devis?cancelled=1&locale=' . $quote->getLocale(),
        ]);
    }

    /**
     * Add the deposit as a one-time invoice item to the customer's first invoice.
     * Called from the webhook after the Subscription is created.
     */
    public function addDepositInvoiceItem(string $customerId, string $subscriptionId, int $depositCents, string $packageName): void
    {
        $this->client->invoiceItems->create([
            'customer' => $customerId,
            'subscription' => $subscriptionId,
            'amount' => $depositCents,
            'currency' => 'cad',
            'description' => sprintf('Acompte 50 %% — création %s', $packageName),
        ]);
    }

    public function getClient(): StripeClient
    {
        return $this->client;
    }
}
