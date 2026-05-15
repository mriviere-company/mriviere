<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ManagedSite;
use App\Entity\SiteDailyStats;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SiteDailyStats>
 */
final class SiteDailyStatsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteDailyStats::class);
    }

    public function findOneByDay(ManagedSite $site, \DateTimeImmutable $day): ?SiteDailyStats
    {
        return $this->findOneBy(['site' => $site, 'day' => $day]);
    }

    /**
     * @return list<SiteDailyStats>
     */
    public function timeseriesForSite(ManagedSite $site, int $days = 30): array
    {
        $since = new \DateTimeImmutable(sprintf('-%d days', $days));

        return $this->createQueryBuilder('s')
            ->where('s.site = :site')
            ->andWhere('s.day >= :since')
            ->setParameter('site', $site)
            ->setParameter('since', $since)
            ->orderBy('s.day', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<SiteDailyStats>
     */
    public function aggregatedTimeseries(int $days = 30): array
    {
        $since = new \DateTimeImmutable(sprintf('-%d days', $days));

        return $this->createQueryBuilder('s')
            ->where('s.day >= :since')
            ->setParameter('since', $since)
            ->orderBy('s.day', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
