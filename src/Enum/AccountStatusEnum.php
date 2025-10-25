<?php

namespace App\Enum;

enum AccountStatusEnum: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case CLOSED = 'closed';
    case PENDING = 'pending';

    public function getLabel(): string
    {
        return match($this) {
            self::ACTIVE => 'enums.account_status.active',
            self::SUSPENDED => 'enums.account_status.suspended',
            self::CLOSED => 'enums.account_status.closed',
            self::PENDING => 'enums.account_status.pending',
        };
    }

    public static function getChoices(): array
    {
        return [
            'Actif' => self::ACTIVE->value,
            'Suspendu' => self::SUSPENDED->value,
            'Fermé' => self::CLOSED->value,
            'En attente' => self::PENDING->value,
        ];
    }
}
