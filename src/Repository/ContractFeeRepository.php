<?php

namespace App\Repository;

use App\Entity\ContractFee;
use App\Entity\CreditApplication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContractFee>
 */
class ContractFeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContractFee::class);
    }

    /**
     * Récupère tous les frais pour une demande de crédit
     */
    public function findByCreditApplication(CreditApplication $creditApplication): array
    {
        return $this->createQueryBuilder('cf')
            ->andWhere('cf.creditApplication = :creditApplication')
            ->setParameter('creditApplication', $creditApplication)
            ->orderBy('cf.feeType', 'ASC')
            ->addOrderBy('cf.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les frais appliqués pour une demande de crédit
     */
    public function findAppliedFeesByCreditApplication(CreditApplication $creditApplication): array
    {
        return $this->createQueryBuilder('cf')
            ->andWhere('cf.creditApplication = :creditApplication')
            ->andWhere('cf.isApplied = :applied')
            ->setParameter('creditApplication', $creditApplication)
            ->setParameter('applied', true)
            ->orderBy('cf.feeType', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le total des frais pour une demande de crédit
     */
    public function getTotalFeesByCreditApplication(CreditApplication $creditApplication): float
    {
        $fees = $this->findAppliedFeesByCreditApplication($creditApplication);
        $total = 0;

        foreach ($fees as $fee) {
            $total += $fee->getCalculatedAmount();
        }

        return $total;
    }

    /**
     * Vérifie si tous les frais requis sont définis pour une demande
     */
    public function hasAllRequiredFees(CreditApplication $creditApplication): bool
    {
        // Types de frais requis par défaut
        $requiredFeeTypes = ['dossier', 'assurance'];
        
        $existingFeeTypes = $this->createQueryBuilder('cf')
            ->select('cf.feeType')
            ->andWhere('cf.creditApplication = :creditApplication')
            ->andWhere('cf.isRequired = :required')
            ->setParameter('creditApplication', $creditApplication)
            ->setParameter('required', true)
            ->getQuery()
            ->getScalarResult();

        $existingTypes = array_column($existingFeeTypes, 'feeType');
        
        return count(array_intersect($requiredFeeTypes, $existingTypes)) === count($requiredFeeTypes);
    }
}
