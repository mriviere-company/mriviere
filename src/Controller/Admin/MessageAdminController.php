<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Config\MessageStatus;
use App\Entity\ContactMessage;
use App\Repository\ContactMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/messages', name: 'api_admin_messages_')]
#[IsGranted('ROLE_ADMIN')]
final class MessageAdminController extends AbstractController
{
    public function __construct(
        private readonly ContactMessageRepository $messages,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = array_map(static fn (ContactMessage $m) => [
            'id' => $m->getId(),
            'name' => $m->getName(),
            'email' => $m->getEmail(),
            'message' => $m->getMessage(),
            'status' => $m->getStatus()->value,
            'createdAt' => $m->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], $this->messages->findRecent());

        return $this->json(['messages' => $items]);
    }

    #[Route('/{id}/read', name: 'mark_read', methods: ['POST'])]
    public function markRead(int $id): JsonResponse
    {
        $msg = $this->messages->find($id);
        if ($msg === null) {
            return $this->json(['error' => 'Message introuvable.'], 404);
        }
        $msg->setStatus(MessageStatus::Read);
        $this->em->flush();
        return $this->json(['ok' => true]);
    }
}
