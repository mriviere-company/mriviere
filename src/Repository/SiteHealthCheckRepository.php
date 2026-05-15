<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ManagedSite;
use App\Entity\SiteHealthCheck;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SiteHealthCheck>
 */
final class SiteHealthCheckRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteHealthCheck::class);
    }

    /**
     * @return list<SiteHealthCheck>
     */
    public function recentForSite(ManagedSite $site, int $limit = 50): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.site = :site')
            ->setParameter('site', $site)
            ->orderBy('h.checkedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count consecutive failed checks ending at the most recent record.
     * Returns 0 if the latest check is OK.
     */
    public function consecutiveFailuresFor(ManagedSite $site): int
    {
        $checks = $this->createQueryBuilder('h')
            ->where('h.site = :site')
            ->setParameter('site', $site)
            ->orderBy('h.checkedAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($checks as $check) {
            if ($check->isOk()) {
                break;
            }
            $count++;
        }
        return $count;
    }
}
