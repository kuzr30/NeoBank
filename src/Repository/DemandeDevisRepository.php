<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DemandeDevis;
use App\Enum\AssuranceType;
use App\Enum\DemandeDevisStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DemandeDevis>
 */
class DemandeDevisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeDevis::class);
    }

    public function save(DemandeDevis $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DemandeDevis $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return DemandeDevis[]
     */
    public function findByTypeAssurance(AssuranceType $type): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.typeAssurance = :type')
            ->setParameter('type', $type)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les demandes par statut
     *
     * @return DemandeDevis[]
     */
    public function findByStatut(DemandeDevisStatusEnum $statut): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByNumeroDevis(string $numeroDevis): ?DemandeDevis
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.numeroDevis = :numeroDevis')
            ->setParameter('numeroDevis', $numeroDevis)
            ->getQuery()
            ->getOneOrNullResult();
    }



    public function countByTypeAssurance(): array
    {
        return $this->createQueryBuilder('d')
            ->select('d.typeAssurance as type, COUNT(d.id) as count')
            ->groupBy('d.typeAssurance')
            ->getQuery()
            ->getResult();
    }



    /**
     * Trouve les demandes en attente d'approbation
     *
     * @return DemandeDevis[]
     */
    public function findPendingApproval(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.statut IN (:statuts)')
            ->setParameter('statuts', [
                DemandeDevisStatusEnum::EN_ATTENTE,
                DemandeDevisStatusEnum::EN_COURS,
                DemandeDevisStatusEnum::TRAITE
            ])
            ->orderBy('d.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les demandes approuvées sans contrat créé
     *
     * @return DemandeDevis[]
     */
    public function findApprovedWithoutContract(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('App\Entity\ContratAssurance', 'c', 'WITH', 'c.demandeDevis = d.id')
            ->where('d.statut = :statut')
            ->andWhere('c.id IS NULL')
            ->setParameter('statut', DemandeDevisStatusEnum::APPROUVE)
            ->orderBy('d.updatedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des demandes de devis
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select([
                'COUNT(d.id) as total',
                'COUNT(CASE WHEN d.statut = :en_attente THEN 1 END) as en_attente',
                'COUNT(CASE WHEN d.statut = :en_cours THEN 1 END) as en_cours',
                'COUNT(CASE WHEN d.statut = :traite THEN 1 END) as traite',
                'COUNT(CASE WHEN d.statut = :approuve THEN 1 END) as approuve',
                'COUNT(CASE WHEN d.statut = :refuse THEN 1 END) as refuse',
                'COUNT(CASE WHEN d.statut = :expire THEN 1 END) as expire'
            ])
            ->setParameter('en_attente', DemandeDevisStatusEnum::EN_ATTENTE)
            ->setParameter('en_cours', DemandeDevisStatusEnum::EN_COURS)
            ->setParameter('traite', DemandeDevisStatusEnum::TRAITE)
            ->setParameter('approuve', DemandeDevisStatusEnum::APPROUVE)
            ->setParameter('refuse', DemandeDevisStatusEnum::REFUSE)
            ->setParameter('expire', DemandeDevisStatusEnum::EXPIRE);

        $result = $qb->getQuery()->getOneOrNullResult();

        return [
            'total' => (int) ($result['total'] ?? 0),
            'en_attente' => (int) ($result['en_attente'] ?? 0),
            'en_cours' => (int) ($result['en_cours'] ?? 0),
            'traite' => (int) ($result['traite'] ?? 0),
            'approuve' => (int) ($result['approuve'] ?? 0),
            'refuse' => (int) ($result['refuse'] ?? 0),
            'expire' => (int) ($result['expire'] ?? 0),
        ];
    }

    /**
     * Trouve les demandes récentes (créées dans les X derniers jours)
     *
     * @return DemandeDevis[]
     */
    public function findRecentDemandes(int $days = 7): array
    {
        $dateLimit = new \DateTimeImmutable('-' . $days . ' days');

        return $this->createQueryBuilder('d')
            ->where('d.createdAt >= :dateLimit')
            ->setParameter('dateLimit', $dateLimit)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de demandes par critères multiples
     *
     * @return DemandeDevis[]
     */
    public function searchDemandes(array $criteria = []): array
    {
        $qb = $this->createQueryBuilder('d');

        if (!empty($criteria['email'])) {
            $qb->andWhere('d.email LIKE :email')
               ->setParameter('email', '%' . $criteria['email'] . '%');
        }

        if (!empty($criteria['nom'])) {
            $qb->andWhere('d.nom LIKE :nom OR d.prenom LIKE :nom')
               ->setParameter('nom', '%' . $criteria['nom'] . '%');
        }

        if (!empty($criteria['statut'])) {
            $qb->andWhere('d.statut = :statut')
               ->setParameter('statut', $criteria['statut']);
        }

        if (!empty($criteria['type_assurance'])) {
            $qb->andWhere('d.typeAssurance = :type_assurance')
               ->setParameter('type_assurance', $criteria['type_assurance']);
        }

        if (!empty($criteria['numero_devis'])) {
            $qb->andWhere('d.numeroDevis LIKE :numero_devis')
               ->setParameter('numero_devis', '%' . $criteria['numero_devis'] . '%');
        }

        if (!empty($criteria['date_creation_from'])) {
            $qb->andWhere('d.createdAt >= :date_from')
               ->setParameter('date_from', $criteria['date_creation_from']);
        }

        if (!empty($criteria['date_creation_to'])) {
            $qb->andWhere('d.createdAt <= :date_to')
               ->setParameter('date_to', $criteria['date_creation_to']);
        }

        return $qb->orderBy('d.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}
