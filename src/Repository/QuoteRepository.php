<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\QuoteStatus;
use App\Entity\Quote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Quote>
 */
class QuoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quote::class);
    }

    public function findById(Uuid $id): ?Quote
    {
        return $this->find($id);
    }

    public function findByCheckoutSession(string $sessionId): ?Quote
    {
        return $this->findOneBy(['stripeCheckoutSessionId' => $sessionId]);
    }

    public function findBySubscription(string $subscriptionId): ?Quote
    {
        return $this->findOneBy(['stripeSubscriptionId' => $subscriptionId]);
    }

    /** @return list<Quote> */
    public function findRecent(int $limit = 100): array
    {
        return $this->createQueryBuilder('q')
            ->orderBy('q.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(QuoteStatus $status): int
    {
        return (int) $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->andWhere('q.status = :s')
            ->setParameter('s', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumMonthlyForActive(): int
    {
        $sum = (int) $this->createQueryBuilder('q')
            ->select('COALESCE(SUM(q.totalMonthlyCents), 0)')
            ->andWhere('q.status IN (:s)')
            ->setParameter('s', [QuoteStatus::DepositPaid, QuoteStatus::Active])
            ->getQuery()
            ->getSingleScalarResult();

        return $sum;
    }
}
