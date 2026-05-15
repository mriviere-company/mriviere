<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\StripeService;
use Stripe\Exception\ApiErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/subscriptions', name: 'api_admin_subscriptions_')]
#[IsGranted('ROLE_ADMIN')]
final class SubscriptionAdminController extends AbstractController
{
    public function __construct(
        private readonly StripeService $stripe,
        private readonly string $stripeDashboardBase,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $subs = $this->stripe->getClient()->subscriptions->all([
                'limit' => 100,
                'status' => 'all',
                'expand' => ['data.customer'],
            ]);
        } catch (ApiErrorException $e) {
            return $this->json(['error' => 'Stripe indisponible : ' . $e->getMessage()], 502);
        }

        $items = [];
        foreach ($subs->data as $sub) {
            $price = $sub->items->data[0]->price ?? null;
            $items[] = [
                'id' => $sub->id,
                'customerEmail' => is_object($sub->customer) ? ($sub->customer->email ?? '') : '',
                'packageName' => $sub->metadata['package'] ?? 'unknown',
                'amount' => $price?->unit_amount ?? 0,
                'currency' => $price?->currency ?? 'eur',
                'status' => $sub->status,
                'currentPeriodEnd' => isset($sub->current_period_end) ? date(\DateTimeInterface::ATOM, $sub->current_period_end) : '',
                'stripeUrl' => $this->stripeDashboardBase . '/subscriptions/' . $sub->id,
            ];
        }

        return $this->json(['subscriptions' => $items]);
    }
}
