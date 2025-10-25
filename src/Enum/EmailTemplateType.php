<?php

namespace App\Enum;

enum EmailTemplateType: string
{
    case KYC_REJECTED = 'kyc_rejected';
    case INCOMPLETE_ACCOUNT = 'incomplete_account';
    case FEES_INQUIRY = 'fees_inquiry';
    case ACCOUNT_ACTIVATION_REMINDER = 'account_activation_reminder';
    case CREDIT_APPLICATION_INCOMPLETE = 'credit_application_incomplete';
    case ACCOUNT_CREATION_FOLLOW_UP = 'account_creation_follow_up';
    case PAYMENT_DETAILS = 'payment_details';

    public function getLabel(): string
    {
        return match($this) {
            self::KYC_REJECTED => 'KYC Rejeté',
            self::INCOMPLETE_ACCOUNT => 'Compte Incomplet',
            self::FEES_INQUIRY => 'Demande de Frais',
            self::ACCOUNT_ACTIVATION_REMINDER => 'Rappel Activation Compte',
            self::CREDIT_APPLICATION_INCOMPLETE => 'Demande de Crédit Incomplète',
            self::ACCOUNT_CREATION_FOLLOW_UP => 'Relance Création de Compte',
            self::PAYMENT_DETAILS => 'Coordonnées bancaires pour virement',
        };
    }

    public function getTranslationKey(): string
    {
        return 'email_template.' . $this->value;
    }

    public function requiresReasons(): bool
    {
        return match($this) {
            self::KYC_REJECTED => true,
            self::INCOMPLETE_ACCOUNT => true,
            self::CREDIT_APPLICATION_INCOMPLETE => true,
            default => false,
        };
    }
}
