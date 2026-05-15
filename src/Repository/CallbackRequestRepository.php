<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\MessageStatus;
use App\Entity\CallbackRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CallbackRequest>
 */
class CallbackRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CallbackRequest::class);
    }

    /** @return list<CallbackRequest> */
    public function findRecent(int $limit = 100): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUnread(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.status = :s')
            ->setParameter('s', MessageStatus::Unread)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
