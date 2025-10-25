<?php

namespace App\Service;

use App\Entity\DemandeDevis;
use App\Entity\CreditApplication;
use App\Entity\DemandeDevisCreditAssociation;
use App\Repository\DemandeDevisCreditAssociationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DemandeDevisCreditAssociationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DemandeDevisCreditAssociationRepository $associationRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Associe automatiquement une demande de devis avec la dernière demande de crédit du même email
     */
    public function autoAssociateWithLatestCredit(DemandeDevis $demandeDevis): ?DemandeDevisCreditAssociation
    {
        try {
            $this->logger->info('Tentative d\'association automatique pour la demande de devis', [
                'devis_id' => $demandeDevis->getId(),
                'email' => $demandeDevis->getEmail()
            ]);

            $association = $this->associationRepository->autoAssociateWithLatestCredit($demandeDevis);

            if ($association) {
                $this->logger->info('Association créée avec succès', [
                    'association_id' => $association->getId(),
                    'credit_reference' => $association->getCreditApplication()->getReferenceNumber()
                ]);
            } else {
                $this->logger->info('Aucune demande de crédit trouvée pour cet email', [
                    'email' => $demandeDevis->getEmail()
                ]);
            }

            return $association;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'association automatique', [
                'error' => $e->getMessage(),
                'devis_id' => $demandeDevis->getId()
            ]);
            return null;
        }
    }

    /**
     * Créé manuellement une association entre une demande de devis et une demande de crédit
     */
    public function createAssociation(
        DemandeDevis $demandeDevis, 
        CreditApplication $creditApplication, 
        ?string $notes = null
    ): DemandeDevisCreditAssociation {
        // Vérifier qu'une association n'existe pas déjà
        $existingAssociation = $this->associationRepository->findActiveAssociation($demandeDevis, $creditApplication);
        
        if ($existingAssociation) {
            return $existingAssociation;
        }

        $association = new DemandeDevisCreditAssociation();
        $association->setDemandeDevis($demandeDevis);
        $association->setCreditApplication($creditApplication);
        $association->setNotes($notes ?? 'Association manuelle');

        $this->entityManager->persist($association);
        $this->entityManager->flush();

        $this->logger->info('Association manuelle créée', [
            'association_id' => $association->getId(),
            'devis_id' => $demandeDevis->getId(),
            'credit_id' => $creditApplication->getId()
        ]);

        return $association;
    }

    /**
     * Récupère toutes les informations de crédit associées à une demande de devis
     */
    public function getCreditInfoForDemandeDevis(DemandeDevis $demandeDevis): array
    {
        $associations = $this->associationRepository->findCreditApplicationsByDemandeDevis($demandeDevis);
        
        $creditInfo = [];
        foreach ($associations as $association) {
            $credit = $association->getCreditApplication();
            $creditInfo[] = [
                'association' => $association,
                'credit' => $credit,
                'reference' => $credit->getReferenceNumber(),
                'amount' => $credit->getLoanAmount(),
                'duration' => $credit->getDuration(),
                'status' => $credit->getStatus(),
                'created_at' => $credit->getCreatedAt()
            ];
        }

        return $creditInfo;
    }

    /**
     * Récupère la dernière demande de crédit associée à une demande de devis
     */
    public function getLatestCreditForDemandeDevis(DemandeDevis $demandeDevis): ?CreditApplication
    {
        $associations = $this->associationRepository->findCreditApplicationsByDemandeDevis($demandeDevis);
        
        if (empty($associations)) {
            return null;
        }

        // Le repository retourne déjà les résultats triés par date de création DESC
        return $associations[0]->getCreditApplication();
    }

    /**
     * Supprime (désactive) une association
     */
    public function removeAssociation(DemandeDevisCreditAssociation $association): void
    {
        $this->associationRepository->deactivateAssociation($association);
        
        $this->logger->info('Association désactivée', [
            'association_id' => $association->getId()
        ]);
    }

    /**
     * Statistiques sur les associations
     */
    public function getAssociationStats(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        $totalAssociations = $qb
            ->select('COUNT(a.id)')
            ->from(DemandeDevisCreditAssociation::class, 'a')
            ->where('a.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        $activeAssociations = $qb
            ->select('COUNT(a.id)')
            ->from(DemandeDevisCreditAssociation::class, 'a')
            ->where('a.isActive = :active')
            ->andWhere('a.createdAt >= :date')
            ->setParameter('active', true)
            ->setParameter('date', new \DateTimeImmutable('-30 days'))
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_associations' => $totalAssociations,
            'recent_associations' => $activeAssociations,
            'percentage_recent' => $totalAssociations > 0 ? round(($activeAssociations / $totalAssociations) * 100, 2) : 0
        ];
    }
}