<?php

namespace App\Twig;

use App\Service\ProfessionalTranslationService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class KycTranslationExtension extends AbstractExtension
{
    public function __construct(
        private ProfessionalTranslationService $translationService,
        private TranslatorInterface $translator
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('translate_kyc_reason', [$this, 'translateKycReason']),
        ];
    }

    public function translateKycReason(string $reason): string
    {
        // Debug: log pour voir ce qui se passe
        error_log("KYC Translation Debug - Reason reçue: " . $reason);
        error_log("KYC Translation Debug - Locale actuelle: " . $this->translationService->getLocale());
        
        // Si c'est directement une clé de traduction (commence par alerts.rejected.common_reasons.)
        if (str_starts_with($reason, 'alerts.rejected.common_reasons.')) {
            error_log("KYC Translation Debug - Clé de traduction détectée: " . $reason);
            
            // Ajouter le préfixe profile_kyc_index.
            $fullKey = 'profile_kyc_index.' . $reason;
            
            // Utiliser directement TranslatorInterface avec locale explicite
            $currentLocale = $this->translationService->getLocale();
            $translatedReason = $this->translator->trans($fullKey, [], 'profile_kyc_index', $currentLocale);
            error_log("KYC Translation Debug - Traduction avec clé complète '$fullKey' et locale '$currentLocale': " . $translatedReason);
            
            // Si pas de traduction, forcer locale nl pour test
            if ($translatedReason === $fullKey) {
                $translatedReason = $this->translator->trans($fullKey, [], 'profile_kyc_index', 'nl');
                error_log("KYC Translation Debug - Traduction forcée 'nl': " . $translatedReason);
            }
            
            return $translatedReason;
        }
        
        // Mapping des raisons courantes français -> clés de traduction
        $reasonsMapping = [
            'Documents non conformes' => 'profile_kyc_index.alerts.rejected.common_reasons.documents_non_conformes',
            'Documents illisibles' => 'profile_kyc_index.alerts.rejected.common_reasons.documents_illegibles',
            'Documents expirés' => 'profile_kyc_index.alerts.rejected.common_reasons.documents_expires',
            'Qualité insuffisante' => 'profile_kyc_index.alerts.rejected.common_reasons.quality_insufficient',
            'Informations manquantes' => 'profile_kyc_index.alerts.rejected.common_reasons.missing_information',
        ];

        // Si la raison est dans notre mapping, on la traduit
        if (isset($reasonsMapping[$reason])) {
            $translationKey = $reasonsMapping[$reason];
            error_log("KYC Translation Debug - Clé trouvée: " . $translationKey);
            
            $currentLocale = $this->translationService->getLocale();
            $translatedReason = $this->translator->trans($translationKey, [], 'profile_kyc_index', $currentLocale);
            error_log("KYC Translation Debug - Traduction: " . $translatedReason);
            
            return $translatedReason;
        }

        // Sinon, on retourne la raison telle quelle
        error_log("KYC Translation Debug - Aucune traduction trouvée, retour original");
        return $reason;
    }
}