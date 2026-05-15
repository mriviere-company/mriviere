<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\ProfileUpdate;
use App\Repository\ProfileContentRepository;
use App\Service\RequestPayloadDeserializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/profile', name: 'api_admin_profile_')]
#[IsGranted('ROLE_ADMIN')]
final class ProfileAdminController extends AbstractController
{
    public function __construct(
        private readonly ProfileContentRepository $repo,
        private readonly RequestPayloadDeserializer $deserializer,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'update', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        [$dto, $errors] = $this->deserializer->deserializeAndValidate($request, ProfileUpdate::class);
        if ($errors !== []) {
            return $this->json(['error' => 'Données invalides.', 'fields' => $errors], 400);
        }
        \assert($dto instanceof ProfileUpdate);

        $profile = $this->repo->getOrCreate();
        $profile->setBio($dto->bio);
        $profile->setStack($dto->stack);
        $profile->setPhotoUrl($dto->photoUrl !== null && $dto->photoUrl !== '' ? $dto->photoUrl : null);
        $this->em->flush();

        return $this->json(['ok' => true]);
    }
}
