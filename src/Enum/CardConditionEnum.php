<?php

namespace App\Enum;

/**
 * Enum pour les conditions particulières des cartes bancaires
 * 
 * Centralise la définition des conditions spécifiques à chaque type de carte
 * pour faciliter la réutilisabilité et la maintenance
 */
enum CardConditionEnum: string
{
    case CLASSIC = 'classic';
    case GOLD = 'gold';
    case PLATINUM = 'platinum';

    /**
     * Returns the translation key for card conditions
     */
    public function getConditionsKey(): string
    {
        return match($this) {
            self::CLASSIC => 'CardConditionEnum.conditions.classic.details',
            self::GOLD => 'CardConditionEnum.conditions.gold.details', 
            self::PLATINUM => 'CardConditionEnum.conditions.platinum.details'
        };
    }

    /**
     * Retourne les frais annuels de la carte
     */
    public function getAnnualFees(): string
    {
        return match($this) {
            self::CLASSIC => '0.00', // Gratuit la première année
            self::GOLD => '60.00',   // 5€/mois x 12 mois
            self::PLATINUM => '180.00' // 15€/mois x 12 mois
        };
    }

    /**
     * Retourne les limites de la carte
     */
    public function getLimits(): array
    {
        return match($this) {
            self::CLASSIC => [
                'daily' => '500.00',
                'monthly' => '2000.00'
            ],
            self::GOLD => [
                'daily' => '1000.00',
                'monthly' => '5000.00',
                'credit' => '2000.00'
            ],
            self::PLATINUM => [
                'daily' => '2500.00',
                'monthly' => '10000.00',
                'credit' => '5000.00'
            ]
        };
    }

    /**
     * Returns the translation key for card label
     */
    public function getLabelKey(): string
    {
        return match($this) {
            self::CLASSIC => 'CardConditionEnum.labels.classic',
            self::GOLD => 'CardConditionEnum.labels.gold',
            self::PLATINUM => 'CardConditionEnum.labels.platinum'
        };
    }

    /**
     * Returns the translation key for card description
     */
    public function getDescriptionKey(): string
    {
        return match($this) {
            self::CLASSIC => 'CardConditionEnum.descriptions.classic',
            self::GOLD => 'CardConditionEnum.descriptions.gold',
            self::PLATINUM => 'CardConditionEnum.descriptions.platinum'
        };
    }

    /**
     * Returns the translation key for card features
     */
    public function getFeaturesKey(): string
    {
        return match($this) {
            self::CLASSIC => 'CardConditionEnum.features.classic',
            self::GOLD => 'CardConditionEnum.features.gold',
            self::PLATINUM => 'CardConditionEnum.features.platinum'
        };
    }

    /**
     * Vérifie si la carte a un crédit revolving
     */
    public function hasCreditLimit(): bool
    {
        return match($this) {
            self::CLASSIC => false,
            self::GOLD => true,
            self::PLATINUM => true
        };
    }

    /**
     * Returns all available card types with translation keys
     */
    public static function getAllTypesKeys(): array
    {
        return [
            self::CLASSIC->value => self::CLASSIC->getLabelKey(),
            self::GOLD->value => self::GOLD->getLabelKey(),
            self::PLATINUM->value => self::PLATINUM->getLabelKey()
        ];
    }

    /**
     * Legacy method for backward compatibility
     * @deprecated Use getLabelKey() with translation service instead
     */
    public function getLabel(): string
    {
        return match($this) {
            self::CLASSIC => 'Carte Classic',
            self::GOLD => 'Carte Gold',
            self::PLATINUM => 'Carte Platinum'
        };
    }

    /**
     * Legacy method for backward compatibility  
     * @deprecated Use getDescriptionKey() with translation service instead
     */
    public function getDescription(): string
    {
        return match($this) {
            self::CLASSIC => 'Carte idéale pour débuter avec des services essentiels',
            self::GOLD => 'Carte premium avec assurances et cashback inclus',
            self::PLATINUM => 'Carte prestige avec services exclusifs et conciergerie'
        };
    }

    /**
     * Legacy method for backward compatibility
     * @deprecated Use getConditionsKey() with translation service instead  
     */
    public function getConditions(): string
    {
        return match($this) {
            self::CLASSIC => "CONDITIONS PARTICULIÈRES - CARTE CLASSIC

TARIFICATION :
- Cotisation annuelle : Gratuite la première année, puis 24€/an
- Retrait en zone euro : Gratuit (3 par mois), puis 1€
- Paiement en devises : 2% de commission

PLAFONDS :
- Retrait quotidien : 500€
- Paiement mensuel : 2 000€

SERVICES INCLUS :
- Assurance achats : 500€
- Assistance téléphonique 24h/7j",

            self::GOLD => "CONDITIONS PARTICULIÈRES - CARTE GOLD

TARIFICATION :
- Cotisation annuelle : 60€
- Retraits gratuits dans le monde entier
- Paiements sans commission en zone euro

PLAFONDS :
- Retrait quotidien : 1 000€
- Paiement mensuel : 5 000€
- Crédit revolving : 2 000€

SERVICES INCLUS :
- Assurance voyage internationale
- Cashback 1% sur tous les achats
- Conciergerie téléphonique
- Assurance achats : 2 000€",

            self::PLATINUM => "CONDITIONS PARTICULIÈRES - CARTE PLATINUM

TARIFICATION :
- Cotisation annuelle : 180€
- Tous services gratuits dans le monde entier
- Taux de change préférentiels

PLAFONDS :
- Retrait quotidien : 2 500€
- Paiement mensuel : 10 000€
- Crédit revolving : 5 000€

SERVICES PREMIUM :
- Assurance voyage premium + famille
- Cashback 2% sur tous les achats
- Accès salons VIP aéroports
- Conciergerie premium 24h/24
- Assurance achats : 5 000€
- Service de rachat de billets d'avion"
        };
    }

    /**
     * Crée une instance depuis une chaîne de caractères
     */
    public static function fromString(string $cardType): ?self
    {
        return match($cardType) {
            'classic' => self::CLASSIC,
            'gold' => self::GOLD,
            'platinum' => self::PLATINUM,
            default => null
        };
    }
}
