<?php

namespace App\Enum;

enum CreditApplicationStatusEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case IN_PROGRESS = 'in_progress'; // En cours (statut par défaut)
    case IN_REVIEW = 'in_review';     // En cours d'étude (par l'admin)
    case CONTRACT_SENT = 'contract_sent';
    case CONTRACT_SIGNED = 'contract_signed';
    case CONTRACT_VALIDATED = 'contract_validated';
    case FUNDS_DISBURSED = 'funds_disbursed';
    case REQUIRES_DOCUMENTS = 'requires_documents';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'enums.credit_application_status.pending',
            self::APPROVED => 'enums.credit_application_status.approved',
            self::REJECTED => 'enums.credit_application_status.rejected',
            self::IN_PROGRESS => 'enums.credit_application_status.in_progress',
            self::IN_REVIEW => 'enums.credit_application_status.in_review',
            self::CONTRACT_SENT => 'enums.credit_application_status.contract_sent',
            self::CONTRACT_SIGNED => 'enums.credit_application_status.contract_signed',
            self::CONTRACT_VALIDATED => 'enums.credit_application_status.contract_validated',
            self::FUNDS_DISBURSED => 'enums.credit_application_status.funds_disbursed',
            self::REQUIRES_DOCUMENTS => 'enums.credit_application_status.requires_documents',
            self::CANCELLED => 'enums.credit_application_status.cancelled',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::PENDING => 'banking__status--pending',
            self::APPROVED => 'banking__status--completed',
            self::REJECTED => 'banking__status--rejected',
            self::IN_PROGRESS => 'banking__status--processing',
            self::IN_REVIEW => 'banking__status--processing',
            self::CONTRACT_SENT => 'banking__status--contract',
            self::CONTRACT_SIGNED => 'banking__status--signed',
            self::CONTRACT_VALIDATED => 'banking__status--validated',
            self::FUNDS_DISBURSED => 'banking__status--disbursed',
            self::REQUIRES_DOCUMENTS => 'banking__status--requires-docs',
            self::CANCELLED => 'banking__status--cancelled',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::PENDING => 'heroicons:clock',
            self::APPROVED => 'heroicons:check-circle',
            self::REJECTED => 'heroicons:x-circle',
            self::IN_PROGRESS => 'heroicons:play',
            self::IN_REVIEW => 'heroicons:document-magnifying-glass',
            self::REQUIRES_DOCUMENTS => 'heroicons:document-plus',
            self::CANCELLED => 'heroicons:no-symbol',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::APPROVED]);
    }

    public function isPending(): bool
    {
        return in_array($this, [self::PENDING, self::IN_PROGRESS, self::IN_REVIEW, self::REQUIRES_DOCUMENTS]);
    }

    /**
     * Méthode statique pour EasyAdmin - retourne les choix pour le formulaire
     */
    public static function getChoices(): array
    {
        return [
            'En attente' => self::PENDING->value,
            'Approuvé' => self::APPROVED->value,
            'Refusé' => self::REJECTED->value,
            'En cours' => self::IN_PROGRESS->value,
            'En cours d\'étude' => self::IN_REVIEW->value,
            'Contrat envoyé' => self::CONTRACT_SENT->value,
            'Contrat signé' => self::CONTRACT_SIGNED->value,
            'Contrat validé' => self::CONTRACT_VALIDATED->value,
            'Fonds débloqués' => self::FUNDS_DISBURSED->value,
            'Documents requis' => self::REQUIRES_DOCUMENTS->value,
            'Annulé' => self::CANCELLED->value,
        ];
    }

    /**
     * Méthode statique pour EasyAdmin - retourne les types de badges
     */
    public static function getBadgeTypes(): array
    {
        return [
            self::PENDING->value => 'warning',
            self::APPROVED->value => 'success',
            self::REJECTED->value => 'danger',
            self::IN_PROGRESS->value => 'info',
            self::IN_REVIEW->value => 'info',
            self::CONTRACT_SENT->value => 'primary',
            self::CONTRACT_SIGNED->value => 'secondary',
            self::CONTRACT_VALIDATED->value => 'success',
            self::FUNDS_DISBURSED->value => 'success',
            self::REQUIRES_DOCUMENTS->value => 'warning',
            self::CANCELLED->value => 'dark',
        ];
    }

    /**
     * Méthode spéciale pour EasyAdmin - retourne les labels en français
     */
    public function getFrenchLabel(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::IN_PROGRESS => 'En cours',
            self::IN_REVIEW => 'En cours d\'étude',
            self::REQUIRES_DOCUMENTS => 'Documents requis',
            self::APPROVED => 'Approuvé',
            self::CONTRACT_SENT => 'Contrat envoyé',
            self::CONTRACT_SIGNED => 'Contrat signé',
            self::CONTRACT_VALIDATED => 'Contrat validé',
            self::FUNDS_DISBURSED => 'Fonds débloqués',
            self::REJECTED => 'Refusé',
            self::CANCELLED => 'Annulé',
        };
    }

    /**
     * Méthode statique pour EasyAdmin - retourne tous les choix en français
     */
    public static function getFrenchChoices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->getFrenchLabel()] = $case;
        }
        return $choices;
    }
}
