<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/admin', name: 'api_admin_')]
final class AuthController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(#[CurrentUser] ?AdminUser $user): JsonResponse
    {
        // The actual authentication is handled by the json_login authenticator in security.yaml.
        // This controller is only reached on success.
        if ($user === null) {
            return $this->json(['error' => 'Identifiants invalides.'], 401);
        }

        $user->touchLogin();
        $this->em->flush();

        return $this->json([
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // Handled by Symfony Security; this controller never returns.
        return $this->json(['ok' => true]);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] ?AdminUser $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['error' => 'Non authentifié.'], 401);
        }

        return $this->json([
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }
}
