<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Package;
use App\Repository\PackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/packages', name: 'api_packages_')]
final class PackageController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(PackageRepository $repo): JsonResponse
    {
        $items = array_map(static fn (Package $p) => [
            'slug' => $p->getSlug()->value,
            'name' => $p->getName(),
            'oneShotPrice' => $p->getOneShotPriceCents(),
            'monthlyPrice' => $p->getMonthlyPriceCents(),
            'maxPages' => $p->getMaxPages(),
            'features' => $p->getFeatures(),
            'highlighted' => $p->isHighlighted(),
        ], $repo->findActiveOrdered());

        return $this->json(['packages' => $items]);
    }
}
