<?php

namespace App\Enum;

enum TransactionTypeEnum: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';

    public function getLabel(): string
    {
        return match($this) {
            self::DEBIT => 'enums.transaction_type.debit',
            self::CREDIT => 'enums.transaction_type.credit',
        };
    }

    public static function getChoices(): array
    {
        return [
            'Débit' => self::DEBIT->value,
            'Crédit' => self::CREDIT->value,
        ];
    }
}
