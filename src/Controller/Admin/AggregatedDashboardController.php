<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\SiteDailyStats;
use App\Repository\ManagedSiteRepository;
use App\Repository\SiteDailyStatsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/aggregated', name: 'api_admin_aggregated_')]
#[IsGranted('ROLE_ADMIN')]
final class AggregatedDashboardController extends AbstractController
{
    public function __construct(
        private readonly ManagedSiteRepository $sites,
        private readonly SiteDailyStatsRepository $dailyStats,
    ) {
    }

    #[Route('', name: 'show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        $rows = $this->dailyStats->aggregatedTimeseries(30);
        $sites = $this->sites->findAllOrdered();

        $byDay = [];
        $globalTopPaths = [];
        foreach ($rows as $row) {
            $key = $row->getDay()->format('Y-m-d');
            $byDay[$key] = ($byDay[$key] ?? 0) + $row->getPageViews();
            foreach ($row->getTopPaths() as $path => $count) {
                $globalTopPaths[$path] = ($globalTopPaths[$path] ?? 0) + $count;
            }
        }
        ksort($byDay);
        arsort($globalTopPaths);
        $globalTopPaths = array_slice($globalTopPaths, 0, 20, true);

        $perSite = [];
        foreach ($sites as $site) {
            $siteRows = array_filter($rows, fn(SiteDailyStats $r) => $r->getSite()->getId() === $site->getId());
            $perSite[] = [
                'id' => $site->getId(),
                'label' => $site->getLabel(),
                'domain' => $site->getDomain(),
                'enabled' => $site->isEnabled(),
                'lastHealthStatus' => $site->getLastHealthStatus(),
                'pageViews30d' => array_sum(array_map(fn(SiteDailyStats $r) => $r->getPageViews(), $siteRows)),
                'uniqueVisitors30d' => array_sum(array_map(fn(SiteDailyStats $r) => $r->getUniqueVisitors(), $siteRows)),
            ];
        }

        return $this->json([
            'totals' => [
                'sites' => count($sites),
                'enabledSites' => count(array_filter($sites, fn($s) => $s->isEnabled())),
                'pageViews30d' => array_sum($byDay),
                'uniqueVisitors30d' => array_sum(array_map(fn(SiteDailyStats $r) => $r->getUniqueVisitors(), $rows)),
            ],
            'timeseries' => array_map(fn(string $day, int $views) => ['day' => $day, 'pageViews' => $views], array_keys($byDay), array_values($byDay)),
            'topPaths' => $globalTopPaths,
            'perSite' => $perSite,
        ]);
    }
}
