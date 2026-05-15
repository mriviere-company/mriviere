<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\ProfileContentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/profile', name: 'api_profile_')]
final class ProfileController extends AbstractController
{
    #[Route('', name: 'get', methods: ['GET'])]
    public function get(ProfileContentRepository $repo): JsonResponse
    {
        $profile = $repo->getOrCreate();

        return $this->json([
            'bio' => $profile->getBio(),
            'stack' => $profile->getStack(),
            'links' => $profile->getLinks(),
            'photoUrl' => $profile->getPhotoUrl(),
        ]);
    }
}
