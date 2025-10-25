<?php

namespace App\Enum;

enum CreditApplicationIncompleteReason: string
{
    case MISSING_INCOME_PROOF = 'missing_income_proof';
    case MISSING_EMPLOYMENT_CONTRACT = 'missing_employment_contract';
    case MISSING_TAX_RETURN = 'missing_tax_return';
    case MISSING_BANK_STATEMENTS = 'missing_bank_statements';
    case INCOMPLETE_FINANCIAL_INFO = 'incomplete_financial_info';
    case MISSING_COLLATERAL_INFO = 'missing_collateral_info';
    case MISSING_CO_BORROWER_INFO = 'missing_co_borrower_info';
    case MISSING_PROJECT_DETAILS = 'missing_project_details';
    case MISSING_CREDIT_AMOUNT = 'missing_credit_amount';
    case MISSING_LOAN_PURPOSE = 'missing_loan_purpose';

    public function getLabel(): string
    {
        return match($this) {
            self::MISSING_INCOME_PROOF => 'Justificatif de revenus manquant',
            self::MISSING_EMPLOYMENT_CONTRACT => 'Contrat de travail manquant',
            self::MISSING_TAX_RETURN => 'Avis d\'imposition manquant',
            self::MISSING_BANK_STATEMENTS => 'Relevés bancaires manquants',
            self::INCOMPLETE_FINANCIAL_INFO => 'Informations financières incomplètes',
            self::MISSING_COLLATERAL_INFO => 'Informations sur la garantie manquantes',
            self::MISSING_CO_BORROWER_INFO => 'Informations sur le co-emprunteur manquantes',
            self::MISSING_PROJECT_DETAILS => 'Détails du projet manquants',
            self::MISSING_CREDIT_AMOUNT => 'Montant du crédit non spécifié',
            self::MISSING_LOAN_PURPOSE => 'Objet du prêt non précisé',
        };
    }

    public function getTranslationKey(): string
    {
        return 'credit_application_incomplete_reason.' . $this->value;
    }

    public function getDescription(): string
    {
        return match($this) {
            self::MISSING_INCOME_PROOF => 'Bulletins de salaire des 3 derniers mois',
            self::MISSING_EMPLOYMENT_CONTRACT => 'CDI, CDD ou statut indépendant',
            self::MISSING_TAX_RETURN => 'Dernier avis d\'imposition',
            self::MISSING_BANK_STATEMENTS => 'Relevés des 3 derniers mois',
            self::INCOMPLETE_FINANCIAL_INFO => 'Charges, crédits en cours, patrimoine',
            self::MISSING_COLLATERAL_INFO => 'Type et valeur de la garantie',
            self::MISSING_CO_BORROWER_INFO => 'Identité et revenus du co-emprunteur',
            self::MISSING_PROJECT_DETAILS => 'Description et budget du projet',
            self::MISSING_CREDIT_AMOUNT => 'Montant souhaité à préciser',
            self::MISSING_LOAN_PURPOSE => 'Usage prévu du crédit',
        };
    }
}
