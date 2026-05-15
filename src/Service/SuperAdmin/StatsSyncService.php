<?php

declare(strict_types=1);

namespace App\Service\SuperAdmin;

use App\Entity\ManagedSite;
use App\Entity\SiteDailyStats;
use App\Repository\ManagedSiteRepository;
use App\Repository\SiteDailyStatsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Pulls yesterday's stats from each enabled managed site and upserts a
 * `SiteDailyStats` row. Designed to be invoked once per day from cron via
 * `app:stats:sync-yesterday`.
 *
 * Single responsibility: fetch + persist daily aggregates. Failures are
 * logged but never thrown — one broken clone shouldn't break the rest of
 * the sync.
 */
final readonly class StatsSyncService
{
    public function __construct(
        private ManagedSiteRepository $sites,
        private SiteDailyStatsRepository $dailyStats,
        private SuperAdminClient $client,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Sync yesterday for every enabled site. Returns a per-site summary keyed by domain.
     *
     * @return array<string, array{status: string, pageViews: int, errorReason: ?string}>
     */
    public function syncYesterday(): array
    {
        $yesterday = (new \DateTimeImmutable('yesterday'))->setTime(0, 0);
        $results = [];

        foreach ($this->sites->findEnabled() as $site) {
            $results[$site->getDomain()] = $this->syncSiteForDay($site, $yesterday);
        }

        return $results;
    }

    /**
     * @return array{status: string, pageViews: int, errorReason: ?string}
     */
    public function syncSiteForDay(ManagedSite $site, \DateTimeImmutable $day): array
    {
        $summary = $this->client->statsSummary($site, $day);

        if (!$summary->isOk()) {
            $this->logger->warning('Stats sync failed', [
                'site' => $site->getDomain(),
                'day' => $day->format('Y-m-d'),
                'status' => $summary->status,
                'error' => $summary->errorReason,
            ]);
            return ['status' => $summary->status, 'pageViews' => 0, 'errorReason' => $summary->errorReason];
        }

        $row = $this->dailyStats->findOneByDay($site, $day) ?? new SiteDailyStats($site, $day);
        $row->setPageViews($summary->pageViews);
        $row->setUniqueVisitors($summary->uniqueVisitors);
        $row->setTopPaths($summary->topPaths);
        $row->touch();
        $this->em->persist($row);
        $this->em->flush();

        return ['status' => 'ok', 'pageViews' => $summary->pageViews, 'errorReason' => null];
    }
}
