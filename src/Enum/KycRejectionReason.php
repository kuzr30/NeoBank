<?php

namespace App\Enum;

enum KycRejectionReason: string
{
    case DOCUMENT_UNREADABLE = 'document_unreadable';
    case PHOTO_BLURRY = 'photo_blurry';
    case DOCUMENT_EXPIRED = 'document_expired';
    case MISSING_FRONT = 'missing_front';
    case MISSING_BACK = 'missing_back';
    case MISSING_SELFIE = 'missing_selfie';
    case INFORMATION_MISMATCH = 'information_mismatch';
    case INVALID_DOCUMENT_TYPE = 'invalid_document_type';
    case POOR_LIGHTING = 'poor_lighting';
    case DOCUMENT_DAMAGED = 'document_damaged';
    case UNDERAGE = 'underage';
    case RESTRICTED_COUNTRY = 'restricted_country';

    public function getLabel(): string
    {
        return match($this) {
            self::DOCUMENT_UNREADABLE => 'Document illisible',
            self::PHOTO_BLURRY => 'Photo floue',
            self::DOCUMENT_EXPIRED => 'Document expiré',
            self::MISSING_FRONT => 'Manque recto du document',
            self::MISSING_BACK => 'Manque verso du document',
            self::MISSING_SELFIE => 'Manque selfie',
            self::INFORMATION_MISMATCH => 'Informations ne correspondent pas',
            self::INVALID_DOCUMENT_TYPE => 'Type de document invalide',
            self::POOR_LIGHTING => 'Éclairage insuffisant',
            self::DOCUMENT_DAMAGED => 'Document endommagé',
            self::UNDERAGE => 'Âge minimum requis non atteint',
            self::RESTRICTED_COUNTRY => 'Pays non accepté',
        };
    }

    public function getTranslationKey(): string
    {
        return 'kyc_rejection_reason.' . $this->value;
    }

    public function getDescription(): string
    {
        return match($this) {
            self::DOCUMENT_UNREADABLE => 'Le document fourni est illisible ou de mauvaise qualité',
            self::PHOTO_BLURRY => 'La photo est floue et ne permet pas une identification claire',
            self::DOCUMENT_EXPIRED => 'Le document d\'identité est expiré',
            self::MISSING_FRONT => 'Le recto du document d\'identité est manquant',
            self::MISSING_BACK => 'Le verso du document d\'identité est manquant',
            self::MISSING_SELFIE => 'Le selfie avec le document est manquant',
            self::INFORMATION_MISMATCH => 'Les informations fournies ne correspondent pas au document',
            self::INVALID_DOCUMENT_TYPE => 'Le type de document fourni n\'est pas accepté',
            self::POOR_LIGHTING => 'L\'éclairage est insuffisant pour vérifier le document',
            self::DOCUMENT_DAMAGED => 'Le document est endommagé ou altéré',
            self::UNDERAGE => 'L\'âge minimum requis pour ouvrir un compte n\'est pas atteint',
            self::RESTRICTED_COUNTRY => 'Votre pays de résidence n\'est pas accepté',
        };
    }
}
