<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ContratAssurance;
use App\Entity\DemandeDevis;
use App\Entity\User;
use App\Enum\ContratAssuranceStatusEnum;
use App\Enum\DemandeDevisStatusEnum;
use App\Repository\ContratAssuranceRepository;
use App\Repository\DemandeDevisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour gérer la souscription d'assurances
 * Convertit les demandes de devis approuvées en contrats d'assurance actifs
 */
class AssuranceSubscriptionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DemandeDevisRepository $demandeDevisRepository,
        private readonly ContratAssuranceRepository $contratAssuranceRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Crée un contrat d'assurance à partir d'une demande de devis approuvée
     */
    public function createContractFromApprovedQuote(DemandeDevis $demandeDevis, User $user): ContratAssurance
    {
        // Vérifications préliminaires
        if (!$demandeDevis->canBeConverted()) {
            throw new \InvalidArgumentException(
                sprintf('La demande de devis #%s ne peut pas être convertie en contrat (statut: %s)', 
                    $demandeDevis->getNumeroDevis(),
                    $demandeDevis->getStatut()->value
                )
            );
        }

        // Vérifier qu'il n'existe pas déjà un contrat pour cette demande
        $existingContract = $this->contratAssuranceRepository->findOneBy(['demandeDevis' => $demandeDevis]);
        if ($existingContract) {
            throw new \InvalidArgumentException(
                sprintf('Un contrat existe déjà pour la demande de devis #%s', 
                    $demandeDevis->getNumeroDevis()
                )
            );
        }

        try {
            $this->entityManager->beginTransaction();

            // Créer le contrat d'assurance
            $contrat = new ContratAssurance();
            $contrat->setUser($user);
            $contrat->setDemandeDevis($demandeDevis);
            $contrat->setTypeAssurance($demandeDevis->getTypeAssurance());
            $contrat->setStatut(ContratAssuranceStatusEnum::ACTIF);
            
            // Générer automatiquement le numéro de contrat
            $contrat->setNumeroContrat($this->generateContractNumber($demandeDevis->getTypeAssurance()));
            
            // Calculer les primes et les dates
            $contrat = $this->calculateContractTerms($contrat, $demandeDevis);

            // Persister le contrat
            $this->entityManager->persist($contrat);

            // Marquer la demande de devis comme convertie
            $demandeDevis->markAsConverted();
            $this->entityManager->persist($demandeDevis);

            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('Contrat d\'assurance créé avec succès', [
                'contract_number' => $contrat->getNumeroContrat(),
                'quote_number' => $demandeDevis->getNumeroDevis(),
                'user_id' => $user->getId(),
                'insurance_type' => $contrat->getTypeAssurance()->value
            ]);

            return $contrat;

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            
            $this->logger->error('Erreur lors de la création du contrat d\'assurance', [
                'quote_number' => $demandeDevis->getNumeroDevis(),
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);

            throw new \RuntimeException(
                'Erreur lors de la création du contrat d\'assurance: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Traite en masse les demandes approuvées sans contrat
     */
    public function processApprovedQuotesWithoutContracts(): array
    {
        $approvedQuotes = $this->demandeDevisRepository->findApprovedWithoutContract();
        $results = [
            'processed' => 0,
            'errors' => 0,
            'details' => []
        ];

        foreach ($approvedQuotes as $quote) {
            try {
                // Récupérer l'utilisateur associé (si la demande a un email qui correspond à un utilisateur)
                $user = $this->findUserForQuote($quote);
                
                if (!$user) {
                    $results['details'][] = [
                        'quote_number' => $quote->getNumeroDevis(),
                        'status' => 'skipped',
                        'reason' => 'Aucun utilisateur trouvé pour cet email'
                    ];
                    continue;
                }

                $contract = $this->createContractFromApprovedQuote($quote, $user);
                
                $results['processed']++;
                $results['details'][] = [
                    'quote_number' => $quote->getNumeroDevis(),
                    'contract_number' => $contract->getNumeroContrat(),
                    'status' => 'created'
                ];

            } catch (\Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'quote_number' => $quote->getNumeroDevis(),
                    'status' => 'error',
                    'reason' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Génère un numéro de contrat unique
     */
    private function generateContractNumber(\App\Enum\AssuranceType $type): string
    {
        $prefix = match($type) {
            \App\Enum\AssuranceType::AUTO => 'CA',
            \App\Enum\AssuranceType::HABITATION => 'CH',
            \App\Enum\AssuranceType::SANTE => 'CS',
            \App\Enum\AssuranceType::VIE => 'CV',
            \App\Enum\AssuranceType::PROFESSIONNELLE => 'CP',
        };

        $year = date('Y');
        $timestamp = time();
        $random = str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

        return sprintf('%s%s%s%s', $prefix, $year, $timestamp, $random);
    }

    /**
     * Calcule les termes du contrat (primes, dates d'échéance, etc.)
     */
    private function calculateContractTerms(ContratAssurance $contrat, DemandeDevis $demandeDevis): ContratAssurance
    {
        // Dates de base
        $now = new \DateTimeImmutable();
        $contrat->setDateDebut($now);
        $contrat->setDateFinPrevue($now->modify('+1 year'));
        $contrat->setProchaineEcheance($now->modify('+1 month'));

        // Calcul des primes basé sur le type d'assurance
        $basePremium = $this->calculateBasePremium($demandeDevis);
        $contrat->setPrimeMensuelle(number_format($basePremium, 2, '.', ''));
        $contrat->setPrimeAnnuelle(number_format($basePremium * 12, 2, '.', ''));

        // Franchise standard basée sur le type
        $contrat->setFranchise(number_format($this->calculateStandardDeductible($demandeDevis->getTypeAssurance()), 2, '.', ''));

        return $contrat;
    }

    /**
     * Calcule la prime de base selon le type d'assurance
     */
    private function calculateBasePremium(DemandeDevis $demandeDevis): float
    {
        // Tarifs de base (à adapter selon votre modèle économique)
        return match($demandeDevis->getTypeAssurance()) {
            \App\Enum\AssuranceType::AUTO => 85.00,
            \App\Enum\AssuranceType::HABITATION => 45.00,
            \App\Enum\AssuranceType::SANTE => 120.00,
            \App\Enum\AssuranceType::VIE => 65.00,
            \App\Enum\AssuranceType::PROFESSIONNELLE => 95.00,
        };
    }

    /**
     * Calcule la franchise standard
     */
    private function calculateStandardDeductible(\App\Enum\AssuranceType $type): float
    {
        return match($type) {
            \App\Enum\AssuranceType::AUTO => 500.00,
            \App\Enum\AssuranceType::HABITATION => 300.00,
            \App\Enum\AssuranceType::SANTE => 50.00,
            \App\Enum\AssuranceType::VIE => 0.00,
            \App\Enum\AssuranceType::PROFESSIONNELLE => 750.00,
        };
    }

    /**
     * Trouve l'utilisateur correspondant à une demande de devis
     */
    private function findUserForQuote(DemandeDevis $quote): ?User
    {
        // Rechercher par email
        $userRepository = $this->entityManager->getRepository(User::class);
        return $userRepository->findOneBy(['email' => $quote->getEmail()]);
    }

    /**
     * Suspend un contrat d'assurance
     */
    public function suspendContract(ContratAssurance $contrat, string $reason = ''): void
    {
        if (!$contrat->canBeSuspended()) {
            throw new \InvalidArgumentException(
                sprintf('Le contrat #%s ne peut pas être suspendu (statut actuel: %s)',
                    $contrat->getNumeroContrat(),
                    $contrat->getStatut()->value
                )
            );
        }

        $contrat->suspend($reason);
        $this->entityManager->persist($contrat);
        $this->entityManager->flush();

        $this->logger->info('Contrat d\'assurance suspendu', [
            'contract_number' => $contrat->getNumeroContrat(),
            'reason' => $reason
        ]);
    }

    /**
     * Réactive un contrat suspendu
     */
    public function reactivateContract(ContratAssurance $contrat): void
    {
        if (!$contrat->canBeReactivated()) {
            throw new \InvalidArgumentException(
                sprintf('Le contrat #%s ne peut pas être réactivé (statut actuel: %s)',
                    $contrat->getNumeroContrat(),
                    $contrat->getStatut()->value
                )
            );
        }

        $contrat->reactivate();
        $this->entityManager->persist($contrat);
        $this->entityManager->flush();

        $this->logger->info('Contrat d\'assurance réactivé', [
            'contract_number' => $contrat->getNumeroContrat()
        ]);
    }

    /**
     * Résilie un contrat d'assurance
     */
    public function cancelContract(ContratAssurance $contrat, string $reason = ''): void
    {
        if (!$contrat->canBeCancelled()) {
            throw new \InvalidArgumentException(
                sprintf('Le contrat #%s ne peut pas être résilié (statut actuel: %s)',
                    $contrat->getNumeroContrat(),
                    $contrat->getStatut()->value
                )
            );
        }

        $contrat->cancel($reason);
        $this->entityManager->persist($contrat);
        $this->entityManager->flush();

        $this->logger->info('Contrat d\'assurance résilié', [
            'contract_number' => $contrat->getNumeroContrat(),
            'reason' => $reason
        ]);
    }
}