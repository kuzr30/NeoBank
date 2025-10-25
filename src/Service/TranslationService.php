<?php

namespace App\Service;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service de traduction professionnel pour Symfony 7
 * Implémente les principes SOLID, DRY et KISS
 */
class TranslationService
{
    private array $translations = [];
    private array $globalTranslations = [];
    private string $defaultLocale;
    private string $currentLocale;
    private string $translationsPath;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
        #[Autowire('%kernel.default_locale%')] private string $kernelDefaultLocale,
        private RequestStack $requestStack
    ) {
        $this->translationsPath = $this->projectDir . '/translations';
        $this->defaultLocale = $this->kernelDefaultLocale;
        $this->currentLocale = $this->getCurrentLocaleFromRequest() ?? $this->defaultLocale;
        $this->loadGlobalTranslations();
    }

    /**
     * Récupère la locale actuelle depuis la requête HTTP
     */
    private function getCurrentLocaleFromRequest(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request?->getLocale();
    }

    /**
     * Principe DRY: Méthode centralisée pour définir la locale
     */
    public function setLocale(string $locale): void
    {
        if ($this->isLocaleAvailable($locale)) {
            $this->currentLocale = $locale;
            $this->loadGlobalTranslations();
        }
    }

    public function getLocale(): string
    {
        return $this->currentLocale;
    }

    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    /**
     * Principe KISS: Méthode simple pour charger les traductions globales
     */
    private function loadGlobalTranslations(): void
    {
        $cacheKey = 'global_' . $this->currentLocale;
        
        if (isset($this->globalTranslations[$cacheKey])) {
            return;
        }

        // Essayer d'abord .yaml puis .yml
        $globalFile = $this->translationsPath . '/messages.' . $this->currentLocale . '.yaml';
        if (!file_exists($globalFile)) {
            $globalFile = $this->translationsPath . '/messages.' . $this->currentLocale . '.yml';
        }
        
        if (file_exists($globalFile)) {
            $this->globalTranslations[$cacheKey] = Yaml::parseFile($globalFile) ?? [];
        } else {
            // Fallback vers la langue par défaut
            $defaultFile = $this->translationsPath . '/messages.' . $this->defaultLocale . '.yaml';
            if (!file_exists($defaultFile)) {
                $defaultFile = $this->translationsPath . '/messages.' . $this->defaultLocale . '.yml';
            }
            if (file_exists($defaultFile)) {
                $this->globalTranslations[$cacheKey] = Yaml::parseFile($defaultFile) ?? [];
            } else {
                $this->globalTranslations[$cacheKey] = [];
            }
        }
    }

    /**
     * Principe DRY: Méthode centralisée pour charger les traductions de page
     */
    public function loadPageTranslations(string $page): void
    {
        $cacheKey = $page . '_' . $this->currentLocale;
        
        if (isset($this->translations[$cacheKey])) {
            return;
        }

        // Essayer d'abord .yaml puis .yml
        $pageFile = $this->translationsPath . '/' . $page . '.' . $this->currentLocale . '.yaml';
        if (!file_exists($pageFile)) {
            $pageFile = $this->translationsPath . '/' . $page . '.' . $this->currentLocale . '.yml';
        }
        
        if (file_exists($pageFile)) {
            $this->translations[$cacheKey] = Yaml::parseFile($pageFile) ?? [];
        } else {
            // Fallback vers la langue par défaut
            $defaultFile = $this->translationsPath . '/' . $page . '.' . $this->defaultLocale . '.yaml';
            if (!file_exists($defaultFile)) {
                $defaultFile = $this->translationsPath . '/' . $page . '.' . $this->defaultLocale . '.yml';
            }
            if (file_exists($defaultFile)) {
                $this->translations[$cacheKey] = Yaml::parseFile($defaultFile) ?? [];
            } else {
                $this->translations[$cacheKey] = [];
            }
        }
    }

    /**
     * Principe KISS: Méthode simple pour récupérer une traduction
     */
    public function trans(string $key, array $parameters = [], ?string $page = null): string
    {
        // Recherche dans les traductions de page
        if ($page) {
            $this->loadPageTranslations($page);
            $cacheKey = $page . '_' . $this->currentLocale;
            $translation = $this->getNestedValue($this->translations[$cacheKey] ?? [], $key);
            
            if ($translation !== null) {
                return $this->interpolate($translation, $parameters);
            }
        }

        // Recherche dans les traductions globales
        $globalCacheKey = 'global_' . $this->currentLocale;
        $translation = $this->getNestedValue($this->globalTranslations[$globalCacheKey] ?? [], $key);
        
        if ($translation !== null) {
            return $this->interpolate($translation, $parameters);
        }

        // Fallback: retourne la clé si aucune traduction trouvée
        return $key;
    }

    /**
     * Principe KISS: Méthode utilitaire pour récupérer une valeur dans un tableau imbriqué
     */
    private function getNestedValue(array $array, string $key): ?string
    {
        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return null;
            }
            $value = $value[$k];
        }

        return is_string($value) ? $value : null;
    }

    /**
     * Principe DRY: Méthode centralisée pour l'interpolation de paramètres
     */
    private function interpolate(string $message, array $parameters): string
    {
        foreach ($parameters as $key => $value) {
            $message = str_replace('{' . $key . '}', (string) $value, $message);
        }

        return $message;
    }

    /**
     * Méthode utilitaire pour récupérer toutes les traductions d'une section
     */
    public function getSection(string $section, ?string $page = null): array
    {
        if ($page) {
            $this->loadPageTranslations($page);
            $cacheKey = $page . '_' . $this->currentLocale;
            return $this->translations[$cacheKey][$section] ?? [];
        }

        $globalCacheKey = 'global_' . $this->currentLocale;
        return $this->globalTranslations[$globalCacheKey][$section] ?? [];
    }

    /**
     * Méthode pour récupérer les langues disponibles
     */
    public function getAvailableLocales(): array
    {
        static $locales = null;
        
        if ($locales === null) {
            $locales = [];
            $files = glob($this->translationsPath . '/messages.*.yaml');
            
            foreach ($files as $file) {
                $filename = basename($file);
                if (preg_match('/messages\.([a-z]{2})\.yaml/', $filename, $matches)) {
                    $locales[] = $matches[1];
                }
            }
        }

        return $locales;
    }

    /**
     * Méthode pour vérifier si une langue est disponible
     */
    public function isLocaleAvailable(string $locale): bool
    {
        return in_array($locale, $this->getAvailableLocales(), true);
    }

    /**
     * Méthode pour vider le cache des traductions
     */
    public function clearCache(): void
    {
        $this->translations = [];
        $this->globalTranslations = [];
        $this->loadGlobalTranslations();
    }
}
