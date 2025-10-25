<?php

namespace App\Repository;

use App\Entity\DemandeDevisCreditAssociation;
use App\Entity\DemandeDevis;
use App\Entity\CreditApplication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DemandeDevisCreditAssociation>
 */
class DemandeDevisCreditAssociationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeDevisCreditAssociation::class);
    }

    /**
     * Trouve l'association active entre une demande de devis et une demande de crédit
     */
    public function findActiveAssociation(DemandeDevis $demandeDevis, CreditApplication $creditApplication): ?DemandeDevisCreditAssociation
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.demandeDevis = :devis')
            ->andWhere('a.creditApplication = :credit')
            ->andWhere('a.isActive = :active')
            ->setParameter('devis', $demandeDevis)
            ->setParameter('credit', $creditApplication)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve la dernière demande de crédit associée à un email
     */
    public function findLatestCreditApplicationByEmail(string $email): ?CreditApplication
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('ca')
            ->from(CreditApplication::class, 'ca')
            ->where('ca.email = :email')
            ->orderBy('ca.createdAt', 'DESC')
            ->setParameter('email', $email)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve toutes les demandes de crédit associées à une demande de devis
     */
    public function findCreditApplicationsByDemandeDevis(DemandeDevis $demandeDevis): array
    {
        return $this->createQueryBuilder('a')
            ->select('a', 'ca')
            ->join('a.creditApplication', 'ca')
            ->andWhere('a.demandeDevis = :devis')
            ->andWhere('a.isActive = :active')
            ->setParameter('devis', $demandeDevis)
            ->setParameter('active', true)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les demandes de devis associées à une demande de crédit
     */
    public function findDemandeDevisByeCreditApplication(CreditApplication $creditApplication): array
    {
        return $this->createQueryBuilder('a')
            ->select('a', 'dd')
            ->join('a.demandeDevis', 'dd')
            ->andWhere('a.creditApplication = :credit')
            ->andWhere('a.isActive = :active')
            ->setParameter('credit', $creditApplication)
            ->setParameter('active', true)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Créé automatiquement l'association entre une demande de devis et la dernière demande de crédit du même email
     */
    public function autoAssociateWithLatestCredit(DemandeDevis $demandeDevis): ?DemandeDevisCreditAssociation
    {
        $latestCredit = $this->findLatestCreditApplicationByEmail($demandeDevis->getEmail());
        
        if (!$latestCredit) {
            return null;
        }

        // Vérifier qu'une association n'existe pas déjà
        $existingAssociation = $this->findActiveAssociation($demandeDevis, $latestCredit);
        if ($existingAssociation) {
            return $existingAssociation;
        }

        // Créer la nouvelle association
        $association = new DemandeDevisCreditAssociation();
        $association->setDemandeDevis($demandeDevis);
        $association->setCreditApplication($latestCredit);
        $association->setNotes('Association automatique basée sur l\'email');

        $this->getEntityManager()->persist($association);
        $this->getEntityManager()->flush();

        return $association;
    }

    /**
     * Désactive une association
     */
    public function deactivateAssociation(DemandeDevisCreditAssociation $association): void
    {
        $association->setIsActive(false);
        $this->getEntityManager()->flush();
    }
}