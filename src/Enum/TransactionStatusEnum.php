<?php

namespace App\Enum;

enum TransactionStatusEnum: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'enums.transaction_status.pending',
            self::COMPLETED => 'enums.transaction_status.completed',
            self::FAILED => 'enums.transaction_status.failed',
            self::CANCELLED => 'enums.transaction_status.cancelled',
        };
    }

    public static function getChoices(): array
    {
        return [
            'En attente' => self::PENDING->value,
            'Terminé' => self::COMPLETED->value,
            'Échoué' => self::FAILED->value,
            'Annulé' => self::CANCELLED->value,
        ];
    }
}
