<?php

declare(strict_types=1);

namespace App\Enum;

enum ContratAssuranceStatusEnum: string
{
    case ACTIF = 'actif';
    case SUSPENDU = 'suspendu';
    case RESILIE = 'resilie';
    case EXPIRE = 'expire';

    public function getLabel(): string
    {
        return match($this) {
            self::ACTIF => 'enums.contrat_assurance_status.actif.label',
            self::SUSPENDU => 'enums.contrat_assurance_status.suspendu.label',
            self::RESILIE => 'enums.contrat_assurance_status.resilie.label',
            self::EXPIRE => 'enums.contrat_assurance_status.expire.label',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::ACTIF => 'Contrat en cours et opérationnel',
            self::SUSPENDU => 'Contrat temporairement suspendu',
            self::RESILIE => 'Contrat définitivement résilié',
            self::EXPIRE => 'Contrat arrivé à échéance',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::ACTIF => 'success',
            self::SUSPENDU => 'warning',
            self::RESILIE => 'danger',
            self::EXPIRE => 'secondary',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::ACTIF => 'heroicons:check-circle',
            self::SUSPENDU => 'heroicons:pause-circle',
            self::RESILIE => 'heroicons:x-circle',
            self::EXPIRE => 'heroicons:clock',
        };
    }

    public static function getActiveStatuses(): array
    {
        return [self::ACTIF];
    }

    public static function getInactiveStatuses(): array
    {
        return [self::SUSPENDU, self::RESILIE, self::EXPIRE];
    }

    public static function getAllStatuses(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public function isActive(): bool
    {
        return $this === self::ACTIF;
    }

    public function canBeReactivated(): bool
    {
        return $this === self::SUSPENDU;
    }

    public function canBeSuspended(): bool
    {
        return $this === self::ACTIF;
    }

    public function canBeTerminated(): bool
    {
        return in_array($this, [self::ACTIF, self::SUSPENDU], true);
    }
}