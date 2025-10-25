<?php

namespace App\Repository;

use App\Entity\AmortizationSchedule;
use App\Entity\CreditApplication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AmortizationSchedule>
 */
class AmortizationScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AmortizationSchedule::class);
    }

    public function save(AmortizationSchedule $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AmortizationSchedule $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve tous les échéanciers pour une demande de crédit donnée
     *
     * @return AmortizationSchedule[]
     */
    public function findByCreditApplication(CreditApplication $creditApplication): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.creditApplication = :creditApplication')
            ->setParameter('creditApplication', $creditApplication)
            ->orderBy('a.month', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre d'échéances pour une demande de crédit
     */
    public function countByCreditApplication(CreditApplication $creditApplication): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.creditApplication = :creditApplication')
            ->setParameter('creditApplication', $creditApplication)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Supprime toutes les échéances pour une demande de crédit
     */
    public function removeAllByCreditApplication(CreditApplication $creditApplication): void
    {
        $this->createQueryBuilder('a')
            ->delete()
            ->andWhere('a.creditApplication = :creditApplication')
            ->setParameter('creditApplication', $creditApplication)
            ->getQuery()
            ->execute();
    }
}
