<?php

namespace App\Repository;

use App\Entity\BankAccount;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BankAccount>
 */
class BankAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BankAccount::class);
    }

    /**
     * Récupère les comptes bancaires actifs d'un utilisateur
     *
     * @param User $user
     * @return BankAccount[]
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.user = :user')
            ->andWhere('b.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un IBAN existe déjà pour un utilisateur
     *
     * @param User $user
     * @param string $iban
     * @param int|null $excludeId
     * @return bool
     */
    public function ibanExistsForUser(User $user, string $iban, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.user = :user')
            ->andWhere('b.iban = :iban')
            ->andWhere('b.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('iban', strtoupper(str_replace([' ', '-'], '', $iban)))
            ->setParameter('active', true);

        if ($excludeId) {
            $qb->andWhere('b.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function save(BankAccount $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(BankAccount $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
