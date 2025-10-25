<?php

namespace App\Twig;

use App\Service\ProfessionalTranslationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension Twig pour simplifier l'utilisation des traductions
 * Principe KISS: Interface simple et intuitive
 */
class TranslationExtension extends AbstractExtension
{
    private ProfessionalTranslationService $translationService;

    public function __construct(ProfessionalTranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    public function getFunctions(): array
    {
        return [
            // Fonction principale de traduction
            new TwigFunction('t', [$this, 'translate']),
            // Fonction pour les traductions de page spécifique
            new TwigFunction('tp', [$this, 'translatePage']),
            // Fonction pour récupérer une section complète
            new TwigFunction('ts', [$this, 'translateSection']),
            // Fonction pour récupérer la locale actuelle
            new TwigFunction('locale', [$this, 'getLocale']),
            // Fonction pour récupérer les langues disponibles
            new TwigFunction('locales', [$this, 'getAvailableLocales']),
        ];
    }

    /**
     * Traduction globale
     * Usage: {{ t('nav.home') }}
     */
    public function translate(string $key, array $parameters = []): string
    {
        return $this->translationService->trans($key, $parameters, 'messages');
    }

    /**
     * Traduction spécifique à une page
     * Usage: {{ tp('hero.title', {}, 'home') }} ou {{ tp('hero.title', 'valeur par défaut') }}
     */
    public function translatePage(string $key, array|string $parameters = [], string $page = 'home'): string
    {
        // Si $parameters est une chaîne, c'est une valeur par défaut (rétrocompatibilité)
        if (is_string($parameters)) {
            $defaultValue = $parameters;
            $parameters = [];
            $result = $this->translationService->trans($key, $parameters, $page);
            // Si la clé n'existe pas, retourner la valeur par défaut
            return $result === $key ? $defaultValue : $result;
        }
        
        return $this->translationService->trans($key, $parameters, $page);
    }

    /**
     * Récupérer une section complète
     * Usage: {% set heroSection = ts('hero', 'home') %}
     */
    public function translateSection(string $section, string $page = 'messages'): array
    {
        return $this->translationService->getSection($section, $page);
    }

    /**
     * Récupérer la locale actuelle
     * Usage: {{ locale() }}
     */
    public function getLocale(): string
    {
        return $this->translationService->getLocale();
    }

    /**
     * Récupérer les langues disponibles
     * Usage: {% for locale in locales() %}
     */
    public function getAvailableLocales(): array
    {
        return $this->translationService->getSupportedLocales();
    }
}
