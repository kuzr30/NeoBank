<?php

namespace App\Repository;

use App\Entity\CardSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour les souscriptions de cartes bancaires
 * 
 * @extends ServiceEntityRepository<CardSubscription>
 *
 * @method CardSubscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method CardSubscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method CardSubscription[]    findAll()
 * @method CardSubscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CardSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CardSubscription::class);
    }

    /**
     * Trouve les souscriptions en attente d'approbation
     */
    public function findPendingSubscriptions(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('s.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les souscriptions d'un utilisateur
     */
    public function findByUser($user): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un utilisateur a une souscription en cours pour un compte
     */
    public function hasActivependingSubscription($user, $account): bool
    {
        $result = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.user = :user')
            ->andWhere('s.account = :account')
            ->andWhere('s.status = :status')
            ->setParameter('user', $user)
            ->setParameter('account', $account)
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    /**
     * Statistiques des souscriptions par type de carte
     */
    public function getSubscriptionStatsByType(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.cardType, COUNT(s.id) as count')
            ->groupBy('s.cardType')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des souscriptions par marque
     */
    public function getSubscriptionStatsByBrand(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.cardBrand, COUNT(s.id) as count')
            ->groupBy('s.cardBrand')
            ->getQuery()
            ->getResult();
    }

    /**
     * Souscriptions des 12 derniers mois
     */
    public function getMonthlySubscriptions(): array
    {
        return $this->createQueryBuilder('s')
            ->select('DATE_FORMAT(s.createdAt, \'%Y-%m\') as month, COUNT(s.id) as count')
            ->where('s.createdAt >= :dateLimit')
            ->setParameter('dateLimit', new \DateTimeImmutable('-12 months'))
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Temps moyen de traitement des souscriptions
     */
    public function getAverageProcessingTime(): ?float
    {
        $result = $this->createQueryBuilder('s')
            ->select('AVG(TIMESTAMPDIFF(HOUR, s.createdAt, s.approvedAt)) as avg_hours')
            ->where('s.status = :status')
            ->andWhere('s.approvedAt IS NOT NULL')
            ->setParameter('status', 'approved')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : null;
    }
}
