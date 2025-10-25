<?php

namespace App\Enum;

/**
 * Enum pour les types de cartes bancaires
 */
enum CardTypeEnum: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';
    case PREPAID = 'prepaid';

    /**
     * Retourne le libellé affiché du type de carte
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DEBIT => 'Carte de débit',
            self::CREDIT => 'Carte de crédit',
            self::PREPAID => 'Carte prépayée'
        };
    }

    /**
     * Retourne la description du type de carte
     */
    public function getDescription(): string
    {
        return match($this) {
            self::DEBIT => 'Débitée directement sur votre compte',
            self::CREDIT => 'Avec facilité de paiement',
            self::PREPAID => 'Rechargeable selon vos besoins'
        };
    }

    /**
     * Retourne tous les types de cartes disponibles
     */
    public static function getAllTypes(): array
    {
        return [
            self::DEBIT->value => self::DEBIT->getLabel(),
            self::CREDIT->value => self::CREDIT->getLabel(),
            self::PREPAID->value => self::PREPAID->getLabel()
        ];
    }
}