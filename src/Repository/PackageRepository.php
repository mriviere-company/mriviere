<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\PackageSlug;
use App\Entity\Package;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Package>
 */
class PackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Package::class);
    }

    /** @return list<Package> */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :true')
            ->setParameter('true', true)
            ->orderBy('p.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneBySlug(PackageSlug $slug): ?Package
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
