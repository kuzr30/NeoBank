<?php

namespace App\Repository;

use App\Entity\CardOpposition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour les oppositions de cartes bancaires
 * 
 * @extends ServiceEntityRepository<CardOpposition>
 *
 * @method CardOpposition|null find($id, $lockMode = null, $lockVersion = null)
 * @method CardOpposition|null findOneBy(array $criteria, array $orderBy = null)
 * @method CardOpposition[]    findAll()
 * @method CardOpposition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CardOppositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CardOpposition::class);
    }

    /**
     * Trouve les oppositions en attente de traitement
     */
    public function findPendingOppositions(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les oppositions d'un utilisateur
     */
    public function findByUser($user): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.requestedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une opposition active pour une carte
     */
    public function findActiveOppositionForCard($card): ?CardOpposition
    {
        return $this->createQueryBuilder('o')
            ->where('o.card = :card')
            ->andWhere('o.status = :status')
            ->setParameter('card', $card)
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Statistiques des oppositions par raison
     */
    public function getOppositionStatsByReason(): array
    {
        return $this->createQueryBuilder('o')
            ->select('o.reason, COUNT(o.id) as count')
            ->groupBy('o.reason')
            ->getQuery()
            ->getResult();
    }

    /**
     * Oppositions des 12 derniers mois
     */
    public function getMonthlyOppositions(): array
    {
        return $this->createQueryBuilder('o')
            ->select('DATE_FORMAT(o.createdAt, \'%Y-%m\') as month, COUNT(o.id) as count')
            ->where('o.createdAt >= :dateLimit')
            ->setParameter('dateLimit', new \DateTimeImmutable('-12 months'))
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
