<?php

namespace App\Enum;

enum AccountTypeEnum: string
{
    case CHECKING = 'checking';
    case SAVINGS = 'savings';
    case BUSINESS = 'business';
    case LOAN = 'loan';

    public function getLabel(): string
    {
        return match($this) {
            self::CHECKING => 'enums.account_type.checking',
            self::SAVINGS => 'enums.account_type.savings',
            self::BUSINESS => 'enums.account_type.business',
            self::LOAN => 'enums.account_type.credit',
        };
    }

    public static function getChoices(): array
    {
        return [
            'Compte courant' => self::CHECKING->value,
            'Compte épargne' => self::SAVINGS->value,
            'Compte professionnel' => self::BUSINESS->value,
            'Compte de prêt' => self::LOAN->value,
        ];
    }
}
