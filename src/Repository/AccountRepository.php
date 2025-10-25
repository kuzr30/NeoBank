<?php

namespace App\Repository;

use App\Entity\Account;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Account>
 */
class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    /**
     * Find active main account by user (only the principal account with IBAN)
     */
    public function findActiveByUser($user): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.subAccountCredit', 'sac')
            ->leftJoin('a.subAccountCard', 'sacard')
            ->leftJoin('a.subAccountSavings', 'sas')
            ->leftJoin('a.subAccountInsurance', 'sai')
            ->addSelect('sac', 'sacard', 'sas', 'sai')
            ->andWhere('a.owner = :user')
            ->andWhere('a.status = :status')
            ->andWhere('a.iban IS NOT NULL')
            ->setParameter('user', $user)
            ->setParameter('status', 'active')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find accounts by type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.type = :type')
            ->setParameter('type', $type)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find accounts with low balance
     */
    public function findWithLowBalance(float $threshold = 100.00): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.balance < :threshold')
            ->andWhere('a.status = :status')
            ->setParameter('threshold', $threshold)
            ->setParameter('status', 'active')
            ->orderBy('a.balance', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find accounts with negative balance
     */
    public function findOverdrawnAccounts(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.balance < 0')
            ->andWhere('a.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('a.balance', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total balance for user
     */
    public function getTotalBalanceForUser($user): float
    {
        $result = $this->createQueryBuilder('a')
            ->select('SUM(a.balance)')
            ->andWhere('a.owner = :user')
            ->andWhere('a.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    /**
     * Find account by IBAN
     */
    public function findByIban(string $iban): ?Account
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.iban = :iban')
            ->setParameter('iban', $iban)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find accounts with recent activity
     */
    public function findWithRecentActivity(\DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.transactions', 't')
            ->andWhere('t.createdAt >= :since')
            ->andWhere('a.status = :status')
            ->setParameter('since', $since)
            ->setParameter('status', 'active')
            ->groupBy('a.id')
            ->orderBy('a.lastTransactionAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
