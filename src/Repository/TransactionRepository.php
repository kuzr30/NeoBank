<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\Account;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    /**
     * Find transactions by account
     */
    public function findByAccount(Account $account, int $limit = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.account = :account OR t.destinationAccount = :account')
            ->setParameter('account', $account)
            ->orderBy('t.createdAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find transactions by type
     */
    public function findByType(string $type, Account $account = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.type = :type')
            ->setParameter('type', $type)
            ->orderBy('t.createdAt', 'DESC');

        if ($account) {
            $qb->andWhere('t.account = :account OR t.destinationAccount = :account')
               ->setParameter('account', $account);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find transactions by status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.status = :status')
            ->setParameter('status', $status)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find transactions by date range
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate, Account $account = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('t.createdAt', 'DESC');

        if ($account) {
            $qb->andWhere('t.account = :account OR t.destinationAccount = :account')
               ->setParameter('account', $account);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find pending transactions
     */
    public function findPendingTransactions(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total amount by type for account
     */
    public function getTotalAmountByType(string $type, Account $account, \DateTimeInterface $startDate = null): float
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.amount)')
            ->andWhere('t.type = :type')
            ->andWhere('t.account = :account OR t.destinationAccount = :account')
            ->andWhere('t.status = :status')
            ->setParameter('type', $type)
            ->setParameter('account', $account)
            ->setParameter('status', 'completed');

        if ($startDate) {
            $qb->andWhere('t.createdAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        $result = $qb->getQuery()->getSingleScalarResult();
        return $result ? (float) $result : 0.0;
    }

    /**
     * Find transactions by category
     */
    public function findByCategory(string $category, Account $account = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.category = :category')
            ->setParameter('category', $category)
            ->orderBy('t.createdAt', 'DESC');

        if ($account) {
            $qb->andWhere('t.account = :account OR t.destinationAccount = :account')
               ->setParameter('account', $account);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find large transactions (above threshold)
     */
    public function findLargeTransactions(float $threshold = 1000.00): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.amount >= :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('t.amount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get monthly spending for account
     */
    public function getMonthlySpending(Account $account, int $year, int $month): float
    {
        $startDate = new \DateTime(sprintf('%d-%02d-01', $year, $month));
        $endDate = clone $startDate;
        $endDate->add(new \DateInterval('P1M'));

        $result = $this->createQueryBuilder('t')
            ->select('SUM(t.amount)')
            ->andWhere('t.account = :account')
            ->andWhere('t.type = :type')
            ->andWhere('t.createdAt >= :startDate')
            ->andWhere('t.createdAt < :endDate')
            ->andWhere('t.status = :status')
            ->setParameter('account', $account)
            ->setParameter('type', 'debit')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    /**
     * Trouve les transactions récentes d'un utilisateur
     */
    public function findRecentTransactionsByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.account', 'a')
            ->leftJoin('t.destinationAccount', 'da')
            ->andWhere('a.owner = :user OR da.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule la variation du solde mensuel pour un compte
     */
    public function getMonthlyBalanceChange(Account $account): float
    {
        $startOfMonth = new \DateTime('first day of this month 00:00:00');
        $endOfMonth = new \DateTime('last day of this month 23:59:59');

        $result = $this->createQueryBuilder('t')
            ->select('
                SUM(CASE WHEN t.type = :credit THEN t.amount ELSE 0 END) as total_credits,
                SUM(CASE WHEN t.type = :debit THEN t.amount ELSE 0 END) as total_debits
            ')
            ->andWhere('t.account = :account')
            ->andWhere('t.createdAt >= :startDate')
            ->andWhere('t.createdAt <= :endDate')
            ->andWhere('t.status = :status')
            ->setParameter('account', $account)
            ->setParameter('startDate', $startOfMonth)
            ->setParameter('endDate', $endOfMonth)
            ->setParameter('credit', 'credit')
            ->setParameter('debit', 'debit')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleResult();

        $credits = $result['total_credits'] ? (float) $result['total_credits'] : 0.0;
        $debits = $result['total_debits'] ? (float) $result['total_debits'] : 0.0;

        return $credits - $debits;
    }

    /**
     * Alias pour findRecentTransactionsByUser
     */
    public function findRecentByUser(User $user, int $limit = 10): array
    {
        return $this->findRecentTransactionsByUser($user, $limit);
    }
}
