<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ContratAssurance;
use App\Entity\User;
use App\Enum\AssuranceType;
use App\Enum\ContratAssuranceStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContratAssurance>
 */
class ContratAssuranceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContratAssurance::class);
    }

    public function save(ContratAssurance $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ContratAssurance $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve tous les contrats actifs d'un utilisateur
     *
     * @return ContratAssurance[]
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', ContratAssuranceStatusEnum::ACTIF)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les contrats d'un utilisateur (tous statuts)
     *
     * @return ContratAssurance[]
     */
    public function findAllByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les contrats par type d'assurance pour un utilisateur
     *
     * @return ContratAssurance[]
     */
    public function findByUserAndType(User $user, AssuranceType $type): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.typeAssurance = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule les statistiques d'assurance pour un utilisateur
     */
    public function getStatisticsByUser(User $user): array
    {
        // Compter tous les contrats
        $totalContracts = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        // Compter les contrats actifs
        $contratsActifs = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.user = :user')
            ->andWhere('c.statut = :actif')
            ->setParameter('user', $user)
            ->setParameter('actif', ContratAssuranceStatusEnum::ACTIF)
            ->getQuery()
            ->getSingleScalarResult();

        // Calculer la prime mensuelle totale (sans CAST)
        $primeMensuelleTotal = $this->createQueryBuilder('c')
            ->select('SUM(c.primeMensuelle)')
            ->where('c.user = :user')
            ->andWhere('c.statut = :actif')
            ->setParameter('user', $user)
            ->setParameter('actif', ContratAssuranceStatusEnum::ACTIF)
            ->getQuery()
            ->getSingleScalarResult();

        // Compter les types d'assurance distincts
        $typesAssurance = $this->createQueryBuilder('c')
            ->select('COUNT(DISTINCT c.typeAssurance)')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'totalContracts' => (int) ($totalContracts ?? 0),
            'contratsActifs' => (int) ($contratsActifs ?? 0),
            'primeMensuelleTotal' => (float) ($primeMensuelleTotal ?? 0),
            'typesAssurance' => (int) ($typesAssurance ?? 0),
        ];
    }

    /**
     * Trouve les contrats expirant bientôt (dans les X jours)
     *
     * @return ContratAssurance[]
     */
    public function findExpiringContracts(int $daysBeforeExpiration = 30): array
    {
        $dateLimit = new \DateTimeImmutable('+' . $daysBeforeExpiration . ' days');

        return $this->createQueryBuilder('c')
            ->where('c.statut = :statut')
            ->andWhere('c.dateExpiration IS NOT NULL')
            ->andWhere('c.dateExpiration <= :dateLimit')
            ->andWhere('c.dateExpiration > :now')
            ->setParameter('statut', ContratAssuranceStatusEnum::ACTIF)
            ->setParameter('dateLimit', $dateLimit)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('c.dateExpiration', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un contrat par son numéro
     */
    public function findByNumeroContrat(string $numeroContrat): ?ContratAssurance
    {
        return $this->createQueryBuilder('c')
            ->where('c.numeroContrat = :numero')
            ->setParameter('numero', $numeroContrat)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Calcule le montant total des primes pour un utilisateur
     */
    public function getTotalPrimesForUser(User $user): float
    {
        $result = $this->createQueryBuilder('c')
            ->select('SUM(CAST(c.primeMensuelle AS DECIMAL(10,2))) as total')
            ->where('c.user = :user')
            ->andWhere('c.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', ContratAssuranceStatusEnum::ACTIF)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Trouve les contrats par statut
     *
     * @return ContratAssurance[]
     */
    public function findByStatut(ContratAssuranceStatusEnum $statut): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de contrats par type d'assurance
     */
    public function getCountByType(): array
    {
        $results = $this->createQueryBuilder('c')
            ->select('c.typeAssurance as type, COUNT(c.id) as count')
            ->where('c.statut = :statut')
            ->setParameter('statut', ContratAssuranceStatusEnum::ACTIF)
            ->groupBy('c.typeAssurance')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $result) {
            $counts[$result['type']] = (int) $result['count'];
        }

        return $counts;
    }

    /**
     * Trouve les contrats récents (créés dans les X derniers jours)
     *
     * @return ContratAssurance[]
     */
    public function findRecentContracts(int $days = 7): array
    {
        $dateLimit = new \DateTimeImmutable('-' . $days . ' days');

        return $this->createQueryBuilder('c')
            ->where('c.createdAt >= :dateLimit')
            ->setParameter('dateLimit', $dateLimit)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de contrats par critères multiples
     *
     * @return ContratAssurance[]
     */
    public function searchContracts(array $criteria = []): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->leftJoin('c.demandeDevis', 'd');

        if (!empty($criteria['user_id'])) {
            $qb->andWhere('c.user = :user_id')
               ->setParameter('user_id', $criteria['user_id']);
        }

        if (!empty($criteria['statut'])) {
            $qb->andWhere('c.statut = :statut')
               ->setParameter('statut', $criteria['statut']);
        }

        if (!empty($criteria['type_assurance'])) {
            $qb->andWhere('c.typeAssurance = :type_assurance')
               ->setParameter('type_assurance', $criteria['type_assurance']);
        }

        if (!empty($criteria['numero_contrat'])) {
            $qb->andWhere('c.numeroContrat LIKE :numero_contrat')
               ->setParameter('numero_contrat', '%' . $criteria['numero_contrat'] . '%');
        }

        if (!empty($criteria['date_creation_from'])) {
            $qb->andWhere('c.createdAt >= :date_from')
               ->setParameter('date_from', $criteria['date_creation_from']);
        }

        if (!empty($criteria['date_creation_to'])) {
            $qb->andWhere('c.createdAt <= :date_to')
               ->setParameter('date_to', $criteria['date_creation_to']);
        }

        return $qb->orderBy('c.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}