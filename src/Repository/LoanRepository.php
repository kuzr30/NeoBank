<?php

namespace App\Repository;

use App\Entity\Loan;
use App\Entity\Account;
use App\Entity\User;
use App\Entity\CreditApplication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Loan>
 */
class LoanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Loan::class);
    }

    /**
     * Find loans by account
     */
    public function findByAccount(Account $account): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.account = :account')
            ->setParameter('account', $account)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active loans
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find loans by status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.status = :status')
            ->setParameter('status', $status)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find overdue loans
     */
    public function findOverdue(): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.payments', 'p')
            ->andWhere('p.dueDate < :now')
            ->andWhere('p.status != :paidStatus')
            ->andWhere('l.status = :activeStatus')
            ->setParameter('now', new \DateTime())
            ->setParameter('paidStatus', 'paid')
            ->setParameter('activeStatus', 'active')
            ->groupBy('l.id')
            ->orderBy('p.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find loans with upcoming payments
     */
    public function findWithUpcomingPayments(\DateTimeInterface $upcomingDays = null): array
    {
        if (!$upcomingDays) {
            $upcomingDays = new \DateTime('+7 days');
        }

        return $this->createQueryBuilder('l')
            ->join('l.payments', 'p')
            ->andWhere('p.dueDate BETWEEN :now AND :upcoming')
            ->andWhere('p.status = :pendingStatus')
            ->andWhere('l.status = :activeStatus')
            ->setParameter('now', new \DateTime())
            ->setParameter('upcoming', $upcomingDays)
            ->setParameter('pendingStatus', 'pending')
            ->setParameter('activeStatus', 'active')
            ->groupBy('l.id')
            ->orderBy('p.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find loans by type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.type = :type')
            ->setParameter('type', $type)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find loans by credit application
     */
    public function findByCreditApplication(CreditApplication $creditApplication): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.creditApplication = :application')
            ->setParameter('application', $creditApplication)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total outstanding amount for account
     */
    public function getTotalOutstandingForAccount(Account $account): float
    {
        $result = $this->createQueryBuilder('l')
            ->select('SUM(l.remainingAmount)')
            ->andWhere('l.account = :account')
            ->andWhere('l.status = :status')
            ->setParameter('account', $account)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    /**
     * Find high-value loans (above threshold)
     */
    public function findHighValueLoans(float $threshold = 50000.00): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.amount >= :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('l.amount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find loans near completion (below remaining percentage)
     */
    public function findNearCompletion(float $remainingPercentage = 10.0): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('(l.remainingAmount / l.amount * 100) <= :percentage')
            ->andWhere('l.status = :status')
            ->setParameter('percentage', $remainingPercentage)
            ->setParameter('status', 'active')
            ->orderBy('l.remainingAmount', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get average interest rate
     */
    public function getAverageInterestRate(): float
    {
        $result = $this->createQueryBuilder('l')
            ->select('AVG(l.interestRate)')
            ->andWhere('l.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    /**
     * Find loans by interest rate range
     */
    public function findByInterestRateRange(float $minRate, float $maxRate): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.interestRate BETWEEN :minRate AND :maxRate')
            ->setParameter('minRate', $minRate)
            ->setParameter('maxRate', $maxRate)
            ->orderBy('l.interestRate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active loans by user
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.account', 'a')
            ->andWhere('a.owner = :user')
            ->andWhere('l.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'active')
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
