<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final readonly class JsonAuthSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        if ($user instanceof AdminUser) {
            $user->touchLogin();
            $this->em->flush();
            return new JsonResponse(['email' => $user->getEmail(), 'roles' => $user->getRoles()]);
        }
        return new JsonResponse(['error' => 'Utilisateur invalide.'], 401);
    }
}
