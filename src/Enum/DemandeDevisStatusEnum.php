<?php

declare(strict_types=1);

namespace App\Enum;

enum DemandeDevisStatusEnum: string
{
    case EN_ATTENTE = 'en_attente';
    case EN_COURS = 'en_cours';
    case TRAITE = 'traite';
    case APPROUVE = 'approuve';
    case REFUSE = 'refuse';
    case EXPIRE = 'expire';

    public function getLabel(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::EN_COURS => 'En cours de traitement',
            self::TRAITE => 'Traité',
            self::APPROUVE => 'Approuvé',
            self::REFUSE => 'Refusé',
            self::EXPIRE => 'Expiré',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'Demande reçue, en attente de traitement',
            self::EN_COURS => 'Demande en cours d\'analyse par nos équipes',
            self::TRAITE => 'Demande traitée, en attente de décision',
            self::APPROUVE => 'Demande approuvée, contrat en cours de création',
            self::REFUSE => 'Demande refusée par nos services',
            self::EXPIRE => 'Demande expirée, non traitée dans les délais',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'warning',
            self::EN_COURS => 'info',
            self::TRAITE => 'primary',
            self::APPROUVE => 'success',
            self::REFUSE => 'danger',
            self::EXPIRE => 'secondary',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'heroicons:clock',
            self::EN_COURS => 'heroicons:cog-6-tooth',
            self::TRAITE => 'heroicons:document-check',
            self::APPROUVE => 'heroicons:check-circle',
            self::REFUSE => 'heroicons:x-circle',
            self::EXPIRE => 'heroicons:exclamation-triangle',
        };
    }

    public static function getPendingStatuses(): array
    {
        return [self::EN_ATTENTE, self::EN_COURS, self::TRAITE];
    }

    public static function getFinalStatuses(): array
    {
        return [self::APPROUVE, self::REFUSE, self::EXPIRE];
    }

    public static function getActiveStatuses(): array
    {
        return [self::EN_ATTENTE, self::EN_COURS, self::TRAITE, self::APPROUVE];
    }

    public function isPending(): bool
    {
        return in_array($this, self::getPendingStatuses(), true);
    }

    public function isFinal(): bool
    {
        return in_array($this, self::getFinalStatuses(), true);
    }

    public function canBeApproved(): bool
    {
        return in_array($this, [self::EN_ATTENTE, self::EN_COURS, self::TRAITE], true);
    }

    public function canBeRejected(): bool
    {
        return in_array($this, [self::EN_ATTENTE, self::EN_COURS, self::TRAITE], true);
    }

    public function canBeProcessed(): bool
    {
        return $this === self::EN_ATTENTE;
    }

    public function canCreateContract(): bool
    {
        return $this === self::APPROUVE;
    }

    public static function getTransitionMap(): array
    {
        return [
            self::EN_ATTENTE->value => [self::EN_COURS, self::TRAITE, self::APPROUVE, self::REFUSE, self::EXPIRE],
            self::EN_COURS->value => [self::TRAITE, self::APPROUVE, self::REFUSE, self::EXPIRE],
            self::TRAITE->value => [self::APPROUVE, self::REFUSE, self::EXPIRE],
            self::APPROUVE->value => [], // État final
            self::REFUSE->value => [], // État final
            self::EXPIRE->value => [], // État final
        ];
    }

    public function canTransitionTo(self $newStatus): bool
    {
        $allowedTransitions = self::getTransitionMap()[$this->value] ?? [];
        return in_array($newStatus, $allowedTransitions, true);
    }
}