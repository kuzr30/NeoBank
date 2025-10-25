<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ContratAssurance;
use App\Entity\User;
use App\Repository\ContratAssuranceRepository;
use App\Repository\DemandeDevisRepository;

/**
 * Service de gestion des assurances
 * 
 * Responsabilités :
 * - Fournir les données d'assurance pour l'affichage
 * - Calculer les statistiques utilisateur
 * - Gérer la logique métier des contrats d'assurance
 * - Interface entre contrôleurs et repositories
 */
class AssuranceService
{
    public function __construct(
        private readonly ContratAssuranceRepository $contratAssuranceRepository,
        private readonly DemandeDevisRepository $demandeDevisRepository
    ) {}

    /**
     * Récupère toutes les données d'assurance pour un utilisateur
     * Format similaire à getCreditData() dans BankingController
     */
    public function getAssuranceData(User $user): array
    {
        // Statistiques des contrats
        $statistiques = $this->contratAssuranceRepository->getStatisticsByUser($user);
        
        // Contrats actifs
        $contratsActifs = $this->contratAssuranceRepository->findActiveByUser($user);
        
        // Tous les contrats pour l'historique
        $tousContrats = $this->contratAssuranceRepository->findAllByUser($user);
        
        // Demandes de devis en cours pour cet utilisateur (si connecté)
        $demandesDevis = [];
        if ($user->getEmail()) {
            $demandesDevis = $this->demandeDevisRepository->findBy([
                'email' => $user->getEmail(),
                'statut' => ['en_attente', 'en_cours']
            ], ['createdAt' => 'DESC']);
        }
        
        // Calculs des métriques
        $primeAnnuelleTotal = $statistiques['primeMensuelleTotal'] * 12;
        $hasContrats = !empty($contratsActifs);
        
        // Préparation des données pour l'affichage (regroupement par type)
        $contratsParType = $this->groupContractsByType($contratsActifs);
        
        // Opportunités d'assurance (types non souscrits)
        $opportunites = $this->getAvailableInsuranceOpportunities($user);

        return [
            'statistiques' => array_merge($statistiques, [
                'primeAnnuelleTotal' => $primeAnnuelleTotal,
                'prochainePrimeDate' => $this->getNextPremiumDate($contratsActifs),
                'contratsExpirant' => $this->getExpiringContractsCount($user),
            ]),
            'contratsActifs' => $contratsActifs,
            'contratsParType' => $contratsParType,
            'tousContrats' => $tousContrats,
            'demandesDevis' => $demandesDevis,
            'hasContrats' => $hasContrats,
            'hasDemandesEnCours' => !empty($demandesDevis),
            'opportunites' => $opportunites,
        ];
    }

    /**
     * Regroupe les contrats par type d'assurance
     */
    private function groupContractsByType(array $contrats): array
    {
        $grouped = [];
        
        foreach ($contrats as $contrat) {
            $type = $contrat->getTypeAssurance()->value;
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $contrat;
        }
        
        return $grouped;
    }

    /**
     * Calcule la date de prochaine prime pour les contrats actifs
     */
    private function getNextPremiumDate(array $contratsActifs): ?\DateTimeImmutable
    {
        $prochaines = [];
        
        foreach ($contratsActifs as $contrat) {
            $prochaine = $contrat->getProchainePrimeDate();
            if ($prochaine) {
                $prochaines[] = $prochaine;
            }
        }
        
        if (empty($prochaines)) {
            return null;
        }
        
        // Retourner la plus proche
        usort($prochaines, fn($a, $b) => $a <=> $b);
        return $prochaines[0];
    }

    /**
     * Compte les contrats expirant dans les 30 prochains jours
     */
    private function getExpiringContractsCount(User $user): int
    {
        $contratsExpirant = $this->contratAssuranceRepository->findExpiringContracts(30);
        
        return count(array_filter($contratsExpirant, fn($contrat) => $contrat->getUser() === $user));
    }

    /**
     * Identifie les opportunités d'assurance (types non encore souscrits)
     */
    private function getAvailableInsuranceOpportunities(User $user): array
    {
        $contratsActifs = $this->contratAssuranceRepository->findActiveByUser($user);
        $typesSouscrits = array_map(fn($contrat) => $contrat->getTypeAssurance(), $contratsActifs);
        
        // Tous les types d'assurance disponibles
        $tousTypes = \App\Enum\AssuranceType::cases();
        
        // Types non encore souscrits
        $opportunites = [];
        foreach ($tousTypes as $type) {
            if (!in_array($type, $typesSouscrits, true)) {
                $opportunites[] = [
                    'type' => $type,
                    'label' => $type->getLabel(),
                    'description' => $type->getDescription(),
                    'icon' => $type->getIcon(),
                ];
            }
        }
        
        return $opportunites;
    }

    /**
     * Vérifie si un utilisateur a des contrats actifs
     */
    public function hasActiveContracts(User $user): bool
    {
        $contrats = $this->contratAssuranceRepository->findActiveByUser($user);
        return !empty($contrats);
    }

    /**
     * Calcule le total des primes mensuelles pour un utilisateur
     */
    public function getTotalMonthlyPremiums(User $user): float
    {
        return $this->contratAssuranceRepository->getTotalPrimesForUser($user);
    }

    /**
     * Récupère les contrats d'un type spécifique pour un utilisateur
     */
    public function getContractsByType(User $user, \App\Enum\AssuranceType $type): array
    {
        return $this->contratAssuranceRepository->findByUserAndType($user, $type);
    }

    /**
     * Vérifie si un utilisateur peut souscrire à un type d'assurance
     */
    public function canSubscribeToType(User $user, \App\Enum\AssuranceType $type): bool
    {
        $contratsExistants = $this->getContractsByType($user, $type);
        
        // Certains types d'assurance peuvent avoir plusieurs contrats (ex: AUTO pour plusieurs véhicules)
        $typesMultiples = [
            \App\Enum\AssuranceType::AUTO,
            \App\Enum\AssuranceType::HABITATION,
            \App\Enum\AssuranceType::VOYAGE,
        ];
        
        if (in_array($type, $typesMultiples, true)) {
            return true; // Peut avoir plusieurs contrats de ce type
        }
        
        // Pour les autres types, un seul contrat actif maximum
        $contratsActifs = array_filter($contratsExistants, fn($contrat) => $contrat->isActif());
        return empty($contratsActifs);
    }

    /**
     * Récupère les statistiques globales pour le dashboard admin
     */
    public function getGlobalStatistics(): array
    {
        return [
            'totalContracts' => count($this->contratAssuranceRepository->findAll()),
            'contratsActifs' => count($this->contratAssuranceRepository->findByStatut(\App\Enum\ContratAssuranceStatusEnum::ACTIF)),
            'contractsSuspendus' => count($this->contratAssuranceRepository->findByStatut(\App\Enum\ContratAssuranceStatusEnum::SUSPENDU)),
            'contratsResilie' => count($this->contratAssuranceRepository->findByStatut(\App\Enum\ContratAssuranceStatusEnum::RESILIE)),
            'contratsParType' => $this->contratAssuranceRepository->getCountByType(),
            'contratsRecents' => $this->contratAssuranceRepository->findRecentContracts(7),
        ];
    }
}