<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ManagedSite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ManagedSite>
 */
class ManagedSiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ManagedSite::class);
    }

    /** @return list<ManagedSite> */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<ManagedSite> */
    public function findEnabled(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.enabled = :true')
            ->setParameter('true', true)
            ->orderBy('s.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByDomain(string $domain): ?ManagedSite
    {
        return $this->findOneBy(['domain' => $domain]);
    }
}
