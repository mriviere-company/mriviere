<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\ContactRequest;
use App\Entity\ContactMessage;
use App\Service\MailerService;
use App\Service\RequestPayloadDeserializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/contact', name: 'api_contact_')]
final class ContactController extends AbstractController
{
    public function __construct(
        private readonly RequestPayloadDeserializer $deserializer,
        private readonly EntityManagerInterface $em,
        private readonly MailerService $mailer,
        private readonly RateLimiterFactory $contactLimiter,
    ) {
    }

    #[Route('', name: 'submit', methods: ['POST'])]
    public function submit(Request $request): JsonResponse
    {
        $limit = $this->contactLimiter->create($request->getClientIp() ?? 'anon')->consume();
        if (!$limit->isAccepted()) {
            return $this->json(['error' => 'Trop de tentatives. Réessayez plus tard.'], 429);
        }

        [$dto, $errors] = $this->deserializer->deserializeAndValidate($request, ContactRequest::class);
        if ($errors !== []) {
            return $this->json(['error' => 'Champs invalides.', 'fields' => $errors], 400);
        }
        \assert($dto instanceof ContactRequest);

        $msg = new ContactMessage($dto->name, $dto->email, $dto->message);
        $msg->setIp($request->getClientIp());
        $this->em->persist($msg);
        $this->em->flush();

        try {
            $this->mailer->sendContactNotification($msg);
        } catch (\Throwable) {
            // Persisting succeeded — surface the message in the back-office even if email fails.
        }

        return $this->json(['ok' => true]);
    }
}
