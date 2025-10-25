<?php

namespace App\Repository;

use App\Entity\LoanPayment;
use App\Entity\Loan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoanPayment>
 */
class LoanPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoanPayment::class);
    }

    /**
     * Find payments by loan
     */
    public function findByLoan(Loan $loan): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.loan = :loan')
            ->setParameter('loan', $loan)
            ->orderBy('p.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find payments by status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', $status)
            ->orderBy('p.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find overdue payments
     */
    public function findOverdue(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.dueDate < :now')
            ->andWhere('p.status = :status')
            ->setParameter('now', new \DateTime())
            ->setParameter('status', 'pending')
            ->orderBy('p.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find upcoming payments
     */
    public function findUpcoming(\DateTimeInterface $upcomingDays = null): array
    {
        if (!$upcomingDays) {
            $upcomingDays = new \DateTime('+7 days');
        }

        return $this->createQueryBuilder('p')
            ->andWhere('p.dueDate BETWEEN :now AND :upcoming')
            ->andWhere('p.status = :status')
            ->setParameter('now', new \DateTime())
            ->setParameter('upcoming', $upcomingDays)
            ->setParameter('status', 'pending')
            ->orderBy('p.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find payments due today
     */
    public function findDueToday(): array
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        return $this->createQueryBuilder('p')
            ->andWhere('p.dueDate >= :today')
            ->andWhere('p.dueDate < :tomorrow')
            ->andWhere('p.status = :status')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('status', 'pending')
            ->orderBy('p.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find paid payments by date range
     */
    public function findPaidByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.paidAt BETWEEN :startDate AND :endDate')
            ->andWhere('p.status = :status')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('status', 'paid')
            ->orderBy('p.paidAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get next payment for loan
     */
    public function getNextPaymentForLoan(Loan $loan): ?LoanPayment
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.loan = :loan')
            ->andWhere('p.status = :status')
            ->andWhere('p.dueDate >= :now')
            ->setParameter('loan', $loan)
            ->setParameter('status', 'pending')
            ->setParameter('now', new \DateTime())
            ->orderBy('p.dueDate', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get last payment for loan
     */
    public function getLastPaymentForLoan(Loan $loan): ?LoanPayment
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.loan = :loan')
            ->andWhere('p.status = :status')
            ->setParameter('loan', $loan)
            ->setParameter('status', 'paid')
            ->orderBy('p.paidAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get total paid amount for loan
     */
    public function getTotalPaidForLoan(Loan $loan): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.amount)')
            ->andWhere('p.loan = :loan')
            ->andWhere('p.status = :status')
            ->setParameter('loan', $loan)
            ->setParameter('status', 'paid')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    /**
     * Get total late fees for loan
     */
    public function getTotalLateFeesForLoan(Loan $loan): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.lateFee)')
            ->andWhere('p.loan = :loan')
            ->andWhere('p.lateFee > 0')
            ->setParameter('loan', $loan)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    /**
     * Find payments with late fees
     */
    public function findWithLateFees(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.lateFee > 0')
            ->orderBy('p.lateFee', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get payment statistics for loan
     */
    public function getPaymentStatsForLoan(Loan $loan): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('
                COUNT(p.id) as totalPayments,
                SUM(CASE WHEN p.status = :paidStatus THEN 1 ELSE 0 END) as paidPayments,
                SUM(CASE WHEN p.status = :pendingStatus THEN 1 ELSE 0 END) as pendingPayments,
                SUM(CASE WHEN p.dueDate < :now AND p.status = :pendingStatus THEN 1 ELSE 0 END) as overduePayments,
                SUM(p.amount) as totalAmount,
                SUM(CASE WHEN p.status = :paidStatus THEN p.amount ELSE 0 END) as paidAmount,
                SUM(p.lateFee) as totalLateFees
            ')
            ->andWhere('p.loan = :loan')
            ->setParameter('loan', $loan)
            ->setParameter('paidStatus', 'paid')
            ->setParameter('pendingStatus', 'pending')
            ->setParameter('now', new \DateTime())
            ->groupBy('p.loan');

        return $qb->getQuery()->getSingleResult();
    }
}
