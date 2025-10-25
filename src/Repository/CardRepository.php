<?php

namespace App\Repository;

use App\Entity\Card;
use App\Entity\Account;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    /**
     * Find active cards by account
     */
    public function findActiveByAccount(Account $account): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.account = :account')
            ->andWhere('c.status = :status')
            ->setParameter('account', $account)
            ->setParameter('status', 'active')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find cards by type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.type = :type')
            ->setParameter('type', $type)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find cards expiring soon
     */
    public function findExpiringSoon(\DateTimeInterface $threshold = null): array
    {
        if (!$threshold) {
            $threshold = new \DateTime('+3 months');
        }

        return $this->createQueryBuilder('c')
            ->andWhere('c.expiryDate <= :threshold')
            ->andWhere('c.status = :status')
            ->setParameter('threshold', $threshold)
            ->setParameter('status', 'active')
            ->orderBy('c.expiryDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find expired cards
     */
    public function findExpired(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.expiryDate <= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('c.expiryDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find blocked cards
     */
    public function findBlocked(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.status = :status')
            ->setParameter('status', 'blocked')
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find card by number (masked search)
     */
    public function findByCardNumber(string $cardNumber): ?Card
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.cardNumber = :cardNumber')
            ->setParameter('cardNumber', $cardNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find cards with high spending (above limit percentage)
     */
    public function findHighSpendingCards(float $limitPercentage = 80.0): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.dailySpent / c.dailyLimit * 100 >= :percentage OR c.monthlySpent / c.monthlyLimit * 100 >= :percentage')
            ->andWhere('c.status = :status')
            ->setParameter('percentage', $limitPercentage)
            ->setParameter('status', 'active')
            ->orderBy('c.dailySpent', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total spending for all cards in account
     */
    public function getTotalSpendingForAccount(Account $account, \DateTimeInterface $startDate = null): float
    {
        $qb = $this->createQueryBuilder('c')
            ->select('SUM(c.dailySpent + c.monthlySpent)')
            ->andWhere('c.account = :account')
            ->andWhere('c.status = :status')
            ->setParameter('account', $account)
            ->setParameter('status', 'active');

        if ($startDate) {
            $qb->andWhere('c.updatedAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        $result = $qb->getQuery()->getSingleScalarResult();
        return $result ? (float) $result : 0.0;
    }

    /**
     * Find cards by PIN attempts (security monitoring)
     */
    public function findWithHighPinAttempts(int $threshold = 3): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.pinAttempts >= :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('c.pinAttempts', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find contactless enabled cards
     */
    public function findContactlessEnabled(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.contactlessEnabled = :enabled')
            ->andWhere('c.status = :status')
            ->setParameter('enabled', true)
            ->setParameter('status', 'active')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
