<?php

namespace App\Twig;

use App\Service\ProfessionalTranslationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension Twig pour fournir les traductions au JavaScript
 */
class JavaScriptTranslationExtension extends AbstractExtension
{
    public function __construct(
        private ProfessionalTranslationService $translationService
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('js_translations', [$this, 'getJavaScriptTranslations']),
        ];
    }

    /**
     * Récupère les traductions pour JavaScript
     * Usage: {{ js_translations('simulation') }} ou {{ js_translations('application') }}
     */
    public function getJavaScriptTranslations(string $domain = 'simulation'): string
    {
        $locale = $this->translationService->getLocale();
        
        // Charger les traductions du domaine spécifié
        $this->translationService->loadPageTranslations($domain);
        
        // Récupérer toutes les traductions pour ce domaine
        if ($domain === 'application') {
            $translations = $this->translationService->getSection('credit_application', $domain);
        } else {
            $translations = $this->translationService->getSection('credit_simulation', $domain);
        }
        
        // Retourner en JSON pour utilisation en JavaScript
        return json_encode($translations, JSON_UNESCAPED_UNICODE);
    }
}
