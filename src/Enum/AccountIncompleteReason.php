<?php

namespace App\Enum;

enum AccountIncompleteReason: string
{
    case MISSING_PERSONAL_INFO = 'missing_personal_info';
    case MISSING_ADDRESS = 'missing_address';
    case MISSING_PHONE = 'missing_phone';
    case MISSING_ID_DOCUMENT = 'missing_id_document';
    case MISSING_PROOF_OF_ADDRESS = 'missing_proof_of_address';
    case INCOMPLETE_PROFILE = 'incomplete_profile';
    case MISSING_EMPLOYMENT_INFO = 'missing_employment_info';
    case MISSING_TAX_INFO = 'missing_tax_info';

    public function getLabel(): string
    {
        return match($this) {
            self::MISSING_PERSONAL_INFO => 'Informations personnelles manquantes',
            self::MISSING_ADDRESS => 'Adresse manquante',
            self::MISSING_PHONE => 'Numéro de téléphone manquant',
            self::MISSING_ID_DOCUMENT => 'Document d\'identité manquant',
            self::MISSING_PROOF_OF_ADDRESS => 'Justificatif de domicile manquant',
            self::INCOMPLETE_PROFILE => 'Profil incomplet',
            self::MISSING_EMPLOYMENT_INFO => 'Informations professionnelles manquantes',
            self::MISSING_TAX_INFO => 'Informations fiscales manquantes',
        };
    }

    public function getTranslationKey(): string
    {
        return 'account_incomplete_reason.' . $this->value;
    }

    public function getDescription(): string
    {
        return match($this) {
            self::MISSING_PERSONAL_INFO => 'Nom, prénom ou date de naissance',
            self::MISSING_ADDRESS => 'Adresse complète requise',
            self::MISSING_PHONE => 'Numéro de téléphone valide requis',
            self::MISSING_ID_DOCUMENT => 'Carte d\'identité ou passeport',
            self::MISSING_PROOF_OF_ADDRESS => 'Facture ou relevé de moins de 3 mois',
            self::INCOMPLETE_PROFILE => 'Plusieurs informations manquent',
            self::MISSING_EMPLOYMENT_INFO => 'Profession et situation professionnelle',
            self::MISSING_TAX_INFO => 'Numéro fiscal et pays de résidence fiscale',
        };
    }
}
