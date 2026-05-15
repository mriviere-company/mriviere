<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\CallbackRequestPayload;
use App\Entity\CallbackRequest;
use App\Service\MailerService;
use App\Service\RequestPayloadDeserializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/callbacks', name: 'api_callbacks_')]
final class CallbackController extends AbstractController
{
    public function __construct(
        private readonly RequestPayloadDeserializer $deserializer,
        private readonly EntityManagerInterface $em,
        private readonly MailerService $mailer,
        private readonly RateLimiterFactory $callbackLimiter,
    ) {
    }

    #[Route('', name: 'submit', methods: ['POST'])]
    public function submit(Request $request): JsonResponse
    {
        $limit = $this->callbackLimiter->create($request->getClientIp() ?? 'anon')->consume();
        if (!$limit->isAccepted()) {
            return $this->json(['error' => 'Trop de demandes. Réessayez dans une heure.'], 429);
        }

        [$dto, $errors] = $this->deserializer->deserializeAndValidate($request, CallbackRequestPayload::class);
        if ($errors !== []) {
            return $this->json(['error' => 'Champs invalides.', 'fields' => $errors], 400);
        }
        \assert($dto instanceof CallbackRequestPayload);

        // Parse the slot in America/Toronto, store as UTC.
        try {
            $slot = new \DateTimeImmutable($dto->slot, new \DateTimeZone('America/Toronto'));
        } catch (\Exception) {
            return $this->json(['error' => 'Date invalide.'], 400);
        }

        // Slot must be in the future and within 30 days.
        $now = new \DateTimeImmutable('now', new \DateTimeZone('America/Toronto'));
        if ($slot < $now) {
            return $this->json(['error' => 'Le créneau doit être dans le futur.', 'fields' => ['slot' => 'Le créneau doit être dans le futur.']], 400);
        }
        $maxFuture = $now->modify('+30 days');
        if ($slot > $maxFuture) {
            return $this->json(['error' => 'Le créneau doit être dans les 30 prochains jours.', 'fields' => ['slot' => 'Le créneau doit être dans les 30 prochains jours.']], 400);
        }

        $callback = new CallbackRequest($dto->name, $dto->email ?? '', $dto->phone, $slot);
        $callback->setMessage($dto->message);
        $callback->setIp($request->getClientIp());

        $this->em->persist($callback);
        $this->em->flush();

        try {
            $this->mailer->sendCallbackNotification($callback);
        } catch (\Throwable) {
            // Persistance OK, l'email est best-effort.
        }

        return $this->json(['ok' => true]);
    }
}
