<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Config\MessageStatus;
use App\Entity\CallbackRequest;
use App\Repository\CallbackRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/callbacks', name: 'api_admin_callbacks_')]
#[IsGranted('ROLE_ADMIN')]
final class CallbackAdminController extends AbstractController
{
    public function __construct(
        private readonly CallbackRequestRepository $callbacks,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = array_map(static fn (CallbackRequest $c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'email' => $c->getEmail(),
            'phone' => $c->getPhone(),
            'preferredSlot' => $c->getPreferredSlot()->format(\DateTimeInterface::ATOM),
            'message' => $c->getMessage(),
            'status' => $c->getStatus()->value,
            'createdAt' => $c->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], $this->callbacks->findRecent());

        return $this->json(['callbacks' => $items]);
    }

    #[Route('/{id}/read', name: 'mark_read', methods: ['POST'])]
    public function markRead(int $id): JsonResponse
    {
        $cb = $this->callbacks->find($id);
        if ($cb === null) {
            return $this->json(['error' => 'Demande introuvable.'], 404);
        }
        $cb->setStatus(MessageStatus::Read);
        $this->em->flush();
        return $this->json(['ok' => true]);
    }

    #[Route('/{id}/archive', name: 'archive', methods: ['POST'])]
    public function archive(int $id): JsonResponse
    {
        $cb = $this->callbacks->find($id);
        if ($cb === null) {
            return $this->json(['error' => 'Demande introuvable.'], 404);
        }
        $cb->setStatus(MessageStatus::Archived);
        $this->em->flush();
        return $this->json(['ok' => true]);
    }
}
