<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Config\QuoteStatus;
use App\Repository\CallbackRequestRepository;
use App\Repository\ContactMessageRepository;
use App\Repository\QuoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin', name: 'api_admin_')]
#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepository $quotes,
        private readonly ContactMessageRepository $messages,
        private readonly CallbackRequestRepository $callbacks,
    ) {
    }

    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        return $this->json([
            'pendingQuotes' => $this->quotes->countByStatus(QuoteStatus::Pending),
            'activeSubscriptions' => $this->quotes->countByStatus(QuoteStatus::Active) + $this->quotes->countByStatus(QuoteStatus::DepositPaid),
            'monthlyRecurring' => $this->quotes->sumMonthlyForActive(),
            'unreadMessages' => $this->messages->countUnread(),
            'pendingCallbacks' => $this->callbacks->countUnread(),
        ]);
    }
}
