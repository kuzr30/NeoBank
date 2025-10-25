<?php

namespace App\Repository;

use App\Entity\Transfer;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transfer>
 */
class TransferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transfer::class);
    }

    /**
     * Trouve les virements actifs pour un utilisateur
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', ['pending', 'executing'])
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les virements en attente pour l'admin
     */
    public function findPendingForAdmin(): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.destinationAccount', 'da')
            ->leftJoin('t.transferCodes', 'tc')
            ->addSelect('u', 'da', 'tc')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('statuses', ['pending', 'executing'])
            ->orderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les virements expirés
     */
    public function findExpired(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.expiresAt < :now')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('statuses', ['pending', 'executing'])
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un utilisateur a des virements non bloqués en cours
     */
    public function hasActiveTransfers(User $user): bool
    {
        $count = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.user = :user')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', ['pending', 'executing'])
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Vérifie si un utilisateur est bloqué
     */
    public function isUserBlocked(User $user): bool
    {
        $count = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.user = :user')
            ->andWhere('t.isAccountBlocked = :blocked')
            ->setParameter('user', $user)
            ->setParameter('blocked', true)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
