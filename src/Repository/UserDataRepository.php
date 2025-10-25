<?php

namespace App\Repository;

use App\Entity\UserData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserData>
 */
class UserDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserData::class);
    }

    /**
     * Récupère les dernières données utilisateur par date de création
     */
    public function findLatest(int $limit = 100): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par email
     */
    public function findByEmail(string $email): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email LIKE :email')
            ->setParameter('email', '%' . $email . '%')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les données par action (login/register)
     */
    public function findByAction(string $action): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.action = :action')
            ->setParameter('action', $action)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques par action
     */
    public function getActionStats(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.action, COUNT(u.id) as count')
            ->groupBy('u.action')
            ->getQuery()
            ->getResult();
    }

    /**
     * Données des dernières 24h
     */
    /**
     * Données des dernières 24h
     */
    public function findLast24Hours(): array
    {
        $yesterday = new \DateTime('-1 day');
        
        return $this->createQueryBuilder('u')
            ->andWhere('u.createdAt >= :yesterday')
            ->setParameter('yesterday', $yesterday)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les données en excluant les emails des administrateurs
     * 
     * @param array $excludeEmails Liste des emails à exclure
     * @return UserData[]
     */
    public function findAllExcludingEmails(array $excludeEmails): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC');

        if (!empty($excludeEmails)) {
            $qb->andWhere('u.email NOT IN (:excludeEmails)')
               ->setParameter('excludeEmails', $excludeEmails);
        }

        return $qb->getQuery()->getResult();
    }
}