<?php

namespace App\Enum;

enum EmailStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case FAILED = 'failed';
    case SCHEDULED = 'scheduled';

    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::SENT => 'Envoyé',
            self::FAILED => 'Échec',
            self::SCHEDULED => 'Programmé',
        };
    }

    public function getBadgeColor(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::SENT => 'success',
            self::FAILED => 'danger',
            self::SCHEDULED => 'info',
        };
    }
}
