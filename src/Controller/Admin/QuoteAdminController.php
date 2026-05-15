<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Quote;
use App\Repository\QuoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/admin/quotes', name: 'api_admin_quotes_')]
#[IsGranted('ROLE_ADMIN')]
final class QuoteAdminController extends AbstractController
{
    public function __construct(private readonly QuoteRepository $quotes)
    {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = array_map(static fn (Quote $q) => [
            'id' => (string) $q->getId(),
            'package' => $q->getPackage()->getSlug()->value,
            'clientName' => $q->getClientName(),
            'clientEmail' => $q->getClientEmail(),
            'status' => $q->getStatus()->value,
            'totalOneShot' => $q->getTotalOneShotCents(),
            'totalMonthly' => $q->getTotalMonthlyCents(),
            'createdAt' => $q->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], $this->quotes->findRecent());

        return $this->json(['quotes' => $items]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function show(string $id): JsonResponse
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\Throwable) {
            return $this->json(['error' => 'Identifiant invalide.'], 400);
        }

        $q = $this->quotes->findById($uuid);
        if ($q === null) {
            return $this->json(['error' => 'Devis introuvable.'], 404);
        }

        return $this->json([
            'id' => (string) $q->getId(),
            'package' => $q->getPackage()->getSlug()->value,
            'clientName' => $q->getClientName(),
            'clientCompany' => $q->getClientCompany(),
            'clientEmail' => $q->getClientEmail(),
            'clientPhone' => $q->getClientPhone(),
            'clientAddress' => $q->getClientAddress(),
            'projectDescription' => $q->getProjectDescription(),
            'totalOneShot' => $q->getTotalOneShotCents(),
            'totalMonthly' => $q->getTotalMonthlyCents(),
            'status' => $q->getStatus()->value,
            'createdAt' => $q->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'stripeCheckoutSessionId' => $q->getStripeCheckoutSessionId(),
            'stripeSubscriptionId' => $q->getStripeSubscriptionId(),
        ]);
    }
}
