<?php

namespace App\Enum;

enum CreditTypeEnum: string
{
    case IMMOBILIER = 'immobilier';
    case AUTO = 'auto';
    case CONSOMMATION = 'consommation';
    case TRAVAUX = 'travaux';
    case PROFESSIONNEL = 'professionnel';
    case PERSONAL = 'personal';
    case HOME_IMPROVEMENT = 'home_improvement';
    case DEBT_CONSOLIDATION = 'debt_consolidation';
    case MORTGAGE = 'mortgage';
    case RENOUVELABLE = 'renouvelable';
    case ETUDIANT = 'etudiant';
    case RELAIS = 'relais';
    case MICROCREDIT = 'microcredit';
    case LEASING = 'leasing';
    case VOYAGE = 'voyage';

    public function getLabel(): string
    {
        return match($this) {
            self::IMMOBILIER => 'credit_types.immobilier.label',
            self::AUTO => 'credit_types.auto.label',
            self::CONSOMMATION => 'credit_types.consommation.label',
            self::TRAVAUX => 'credit_types.travaux.label',
            self::PROFESSIONNEL => 'credit_types.professionnel.label',
            self::PERSONAL => 'credit_types.personal.label',
            self::HOME_IMPROVEMENT => 'credit_types.home_improvement.label',
            self::DEBT_CONSOLIDATION => 'credit_types.debt_consolidation.label',
            self::MORTGAGE => 'credit_types.mortgage.label',
            self::RENOUVELABLE => 'credit_types.renouvelable.label',
            self::ETUDIANT => 'credit_types.etudiant.label',
            self::RELAIS => 'credit_types.relais.label',
            self::MICROCREDIT => 'credit_types.microcredit.label',
            self::LEASING => 'credit_types.leasing.label',
            self::VOYAGE => 'credit_types.voyage.label',
        };
    }

    public function getRate(): float
    {
        return match($this) {
            self::IMMOBILIER => 1.5,
            self::AUTO => 2.2,
            self::CONSOMMATION => 2.8,
            self::TRAVAUX => 2.5,
            self::PROFESSIONNEL => 3.2,
            self::PERSONAL => 2.9,
            self::HOME_IMPROVEMENT => 2.5,
            self::DEBT_CONSOLIDATION => 3.2,
            self::MORTGAGE => 2.5,
            self::RENOUVELABLE => 2.5,
            self::ETUDIANT => 2.0,
            self::RELAIS => 2.0,
            self::MICROCREDIT => 2.8,
            self::LEASING => 3.5,
            self::VOYAGE => 3.0,
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::IMMOBILIER => 'credit_types.immobilier.description',
            self::AUTO => 'credit_types.auto.description',
            self::CONSOMMATION => 'credit_types.consommation.description',
            self::TRAVAUX => 'credit_types.travaux.description',
            self::PROFESSIONNEL => 'credit_types.professionnel.description',
            self::PERSONAL => 'credit_types.personal.description',
            self::HOME_IMPROVEMENT => 'credit_types.home_improvement.description',
            self::DEBT_CONSOLIDATION => 'credit_types.debt_consolidation.description',
            self::MORTGAGE => 'credit_types.mortgage.description',
            self::RENOUVELABLE => 'credit_types.renouvelable.description',
            self::ETUDIANT => 'credit_types.etudiant.description',
            self::RELAIS => 'credit_types.relais.description',
            self::MICROCREDIT => 'credit_types.microcredit.description',
            self::LEASING => 'credit_types.leasing.description',
            self::VOYAGE => 'credit_types.voyage.description',
        };
    }

    /**
     * Méthode spéciale pour EasyAdmin - retourne les labels en français
     */
    public function getFrenchLabel(): string
    {
        return match($this) {
            self::IMMOBILIER => 'Immobilier',
            self::AUTO => 'Auto',
            self::CONSOMMATION => 'Consommation',
            self::TRAVAUX => 'Travaux',
            self::PROFESSIONNEL => 'Professionnel',
            self::PERSONAL => 'Personnel',
            self::HOME_IMPROVEMENT => 'Amélioration de l\'habitat',
            self::DEBT_CONSOLIDATION => 'Rachat de crédit',
            self::MORTGAGE => 'Hypothèque',
            self::RENOUVELABLE => 'Crédit renouvelable',
            self::ETUDIANT => 'Crédit étudiant',
            self::RELAIS => 'Crédit relais',
            self::MICROCREDIT => 'Microcrédit',
            self::LEASING => 'Leasing',
            self::VOYAGE => 'Crédit voyage',
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

    /**
     * Méthode statique pour EasyAdmin - retourne les badges avec les valeurs des enums
     */
    public static function getBadgeChoices(): array
    {
        return [
            self::IMMOBILIER->value => 'info',
            self::AUTO->value => 'primary',
            self::CONSOMMATION->value => 'success',
            self::TRAVAUX->value => 'warning',
            self::PROFESSIONNEL->value => 'dark',
            self::PERSONAL->value => 'success',
            self::HOME_IMPROVEMENT->value => 'warning',
            self::DEBT_CONSOLIDATION->value => 'secondary',
            self::MORTGAGE->value => 'info',
            self::RENOUVELABLE->value => 'primary',
            self::ETUDIANT->value => 'light',
            self::RELAIS->value => 'info',
            self::MICROCREDIT->value => 'success',
            self::LEASING->value => 'primary',
            self::VOYAGE->value => 'danger',
        ];
    }
}
