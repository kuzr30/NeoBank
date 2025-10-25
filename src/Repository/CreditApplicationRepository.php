<?php

namespace App\Repository;

use App\Entity\CreditApplication;
use App\Entity\User;
use App\Enum\CreditApplicationStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends ServiceEntityRepository<CreditApplication>
 */
class CreditApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, CreditApplication::class);
    }

    public function save(CreditApplication $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CreditApplication $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les demandes par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('ca')
            ->andWhere('ca.status = :status')
            ->setParameter('status', $status)
            ->orderBy('ca.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les demandes récentes
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('ca')
            ->orderBy('ca.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques par statut
     */
    public function getStatusStatistics(): array
    {
        return $this->createQueryBuilder('ca')
            ->select('ca.status, COUNT(ca.id) as count')
            ->groupBy('ca.status')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère toutes les demandes de crédit d'un utilisateur
     */
    public function findByUserEmail(string $email): array
    {
        return $this->createQueryBuilder('ca')
            ->where('ca.email = :email')
            ->setParameter('email', $email)
            ->orderBy('ca.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les crédits approuvés d'un utilisateur
     */
    public function findApprovedByUserEmail(string $email): array
    {
        return $this->createQueryBuilder('ca')
            ->where('ca.email = :email')
            ->andWhere('ca.status = :status')
            ->setParameter('email', $email)
            ->setParameter('status', CreditApplicationStatusEnum::APPROVED)
            ->orderBy('ca.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques de crédit pour un utilisateur
     */
    public function getUserCreditStats(string $email): array
    {
        // Nombre de crédits validés (fonds débloqués)
        $validatedCount = $this->createQueryBuilder('ca')
            ->select('COUNT(ca.id)')
            ->where('ca.email = :email')
            ->andWhere('ca.status = :status')
            ->setParameter('email', $email)
            ->setParameter('status', CreditApplicationStatusEnum::FUNDS_DISBURSED)
            ->getQuery()
            ->getSingleScalarResult();

        // Nombre total de crédits approuvés (tous statuts actifs)
        $approvedCount = $this->createQueryBuilder('ca')
            ->select('COUNT(ca.id)')
            ->where('ca.email = :email')
            ->andWhere('ca.status IN (:statuses)')
            ->setParameter('email', $email)
            ->setParameter('statuses', [
                CreditApplicationStatusEnum::APPROVED,
                CreditApplicationStatusEnum::CONTRACT_SENT,
                CreditApplicationStatusEnum::CONTRACT_SIGNED,
                CreditApplicationStatusEnum::CONTRACT_VALIDATED,
                CreditApplicationStatusEnum::FUNDS_DISBURSED
            ])
            ->getQuery()
            ->getSingleScalarResult();

        $inProgressCount = $this->createQueryBuilder('ca')
            ->select('COUNT(ca.id)')
            ->where('ca.email = :email')
            ->andWhere('ca.status IN (:statuses)')
            ->setParameter('email', $email)
            ->setParameter('statuses', [
                CreditApplicationStatusEnum::PENDING,
                CreditApplicationStatusEnum::IN_PROGRESS,
                CreditApplicationStatusEnum::IN_REVIEW,
                CreditApplicationStatusEnum::REQUIRES_DOCUMENTS
            ])
            ->getQuery()
            ->getSingleScalarResult();

        $rejectedCount = $this->createQueryBuilder('ca')
            ->select('COUNT(ca.id)')
            ->where('ca.email = :email')
            ->andWhere('ca.status = :status')
            ->setParameter('email', $email)
            ->setParameter('status', CreditApplicationStatusEnum::REJECTED)
            ->getQuery()
            ->getSingleScalarResult();

        // Récupérer les totaux pour tous les crédits approuvés
        $approvedTotals = $this->createQueryBuilder('ca')
            ->select('
                SUM(ca.loanAmount) as totalCreditAmount,
                AVG(ca.duration) as avgDuration,
                SUM(ca.monthlyPayment) as totalMonthlyPayments
            ')
            ->where('ca.email = :email')
            ->andWhere('ca.status IN (:statuses)')
            ->setParameter('email', $email)
            ->setParameter('statuses', [
                CreditApplicationStatusEnum::APPROVED,
                CreditApplicationStatusEnum::CONTRACT_SENT,
                CreditApplicationStatusEnum::CONTRACT_SIGNED,
                CreditApplicationStatusEnum::CONTRACT_VALIDATED,
                CreditApplicationStatusEnum::FUNDS_DISBURSED
            ])
            ->getQuery()
            ->getSingleResult();

        // Calculer le solde crédit disponible dans les sous-comptes
        $availableCreditQuery = $this->entityManager->createQuery('
            SELECT SUM(sac.amount) as totalCreditBalance
            FROM App\Entity\SubAccountCredit sac
            JOIN sac.account a
            JOIN a.owner u
            WHERE u.email = :email
        ');
        $availableCreditQuery->setParameter('email', $email);
        $totalCreditBalance = $availableCreditQuery->getSingleScalarResult() ?? 0;

        // Calculer le solde de base des comptes
        $baseBalanceQuery = $this->entityManager->createQuery('
            SELECT SUM(a.balance) as totalBaseBalance
            FROM App\Entity\Account a
            JOIN a.owner u
            WHERE u.email = :email AND a.type = :type
        ');
        $baseBalanceQuery->setParameter('email', $email);
        $baseBalanceQuery->setParameter('type', 'checking');
        $totalBaseBalance = $baseBalanceQuery->getSingleScalarResult() ?? 0;

        return [
            'validatedCount' => (int) $validatedCount, // Nombre de crédits validés (fonds débloqués)
            'approvedCount' => (int) $approvedCount, // Nombre total de crédits approuvés
            'inProgressCount' => (int) $inProgressCount,
            'rejectedCount' => (int) $rejectedCount,
            'totalCreditAmount' => (float) ($approvedTotals['totalCreditAmount'] ?? 0), // Montant total des crédits
            'totalCreditBalance' => (float) $totalCreditBalance, // Solde crédit disponible (sous-comptes)
            'totalBaseBalance' => (float) $totalBaseBalance, // Solde de base des comptes
            'totalAvailableBalance' => (float) $totalBaseBalance + (float) $totalCreditBalance, // Solde total
            'avgDuration' => $approvedTotals['avgDuration'] ? round((float) $approvedTotals['avgDuration'], 1) : 0,
            'totalMonthlyPayments' => (float) ($approvedTotals['totalMonthlyPayments'] ?? 0), // Mensualités totales
        ];
    }
}
