<?php

namespace App\Service;

use App\Service\CompanySettingsService;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service de traduction professionnel et robuste pour Symfony 7
 * Implémente les principes SOLID, gestion d'erreurs robuste et cache intelligent
 */
final class ProfessionalTranslationService
{
    private array $translations = [];
    private array $fallbackCache = [];
    private string $currentLocale;
    private readonly string $translationsPath;
    private array $supportedLocales = ['fr', 'nl', 'de', 'en', 'es'];

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        #[Autowire('%kernel.default_locale%')] private readonly string $defaultLocale,
        private readonly RequestStack $requestStack,
        private readonly CompanySettingsService $companySettingsService
    ) {
        $this->translationsPath = $this->projectDir . '/translations';
        $this->currentLocale = $this->getCurrentLocaleFromRequest() ?? $this->defaultLocale;
        $this->validateAndNormalizeLocale();
    }

    /**
     * Méthode principale de traduction avec gestion d'erreurs robuste
     */
    public function trans(string $key, array $parameters = [], string $domain = 'application'): string
    {
        try {
            // Validation des paramètres d'entrée
            if (empty($key)) {
                return '';
            }

            // Injection automatique des variables d'entreprise
            $companyVariables = [
                'company_name' => $this->companySettingsService->getCompanyName() ?? 'SEDEF BANK',
                'company_phone' => $this->companySettingsService->getPhone() ?? '+33 1 23 45 67 89',
                'company_email' => $this->companySettingsService->getEmail() ?? 'contact@sedef.fr',
                'company_address' => $this->companySettingsService->getAddress() ?? '3 Rue du Commandant Cousteau, 91300 Massy',
                'company_website' => $this->companySettingsService->getWebsite() ?? 'www.sedef.fr',
            ];

            // Fusion avec les paramètres fournis (les paramètres fournis ont la priorité)
            $parameters = array_merge($companyVariables, $parameters);

            // Chargement des traductions pour le domaine
            $this->loadTranslations($domain);

            // Récupération de la traduction
            $translation = $this->getTranslation($key, $domain);

            // Interpolation des paramètres
            return $this->interpolateParameters($translation, $parameters);

        } catch (\Exception $e) {
            // Log d'erreur et fallback gracieux
            error_log("Translation error for key '{$key}': " . $e->getMessage());
            return $this->getFallbackTranslation($key, $parameters, $domain);
        }
    }

    /**
     * Récupération de la locale actuelle depuis la requête
     */
    private function getCurrentLocaleFromRequest(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        $locale = $request?->getLocale();
        
        return $this->isValidLocale($locale) ? $locale : null;
    }

    /**
     * Validation et normalisation de la locale
     */
    private function validateAndNormalizeLocale(): void
    {
        if (!$this->isValidLocale($this->currentLocale)) {
            $this->currentLocale = $this->defaultLocale;
        }
    }

    /**
     * Validation de la locale
     */
    private function isValidLocale(?string $locale): bool
    {
        return $locale && in_array($locale, $this->supportedLocales, true);
    }

    /**
     * Chargement intelligent des traductions avec cache
     */
    private function loadTranslations(string $domain): void
    {
        $cacheKey = "{$domain}_{$this->currentLocale}";
        
        if (isset($this->translations[$cacheKey])) {
            return;
        }

        $translationFile = $this->getTranslationFilePath($domain, $this->currentLocale);
        
        if (file_exists($translationFile)) {
            $this->translations[$cacheKey] = $this->parseTranslationFile($translationFile);
        } else {
            // Fallback vers la langue par défaut
            $fallbackFile = $this->getTranslationFilePath($domain, $this->defaultLocale);
            if (file_exists($fallbackFile)) {
                $this->translations[$cacheKey] = $this->parseTranslationFile($fallbackFile);
            } else {
                $this->translations[$cacheKey] = [];
            }
        }
    }

    /**
     * Construction du chemin du fichier de traduction
     */
    private function getTranslationFilePath(string $domain, string $locale): string
    {
        return "{$this->translationsPath}/{$domain}.{$locale}.yaml";
    }

    /**
     * Parsing sécurisé du fichier de traduction
     */
    private function parseTranslationFile(string $filePath): array
    {
        try {
            $content = Yaml::parseFile($filePath);
            return is_array($content) ? $content : [];
        } catch (\Exception $e) {
            error_log("Failed to parse translation file '{$filePath}': " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupération d'une traduction avec navigation dans l'arbre
     */
    private function getTranslation(string $key, string $domain): string
    {
        $cacheKey = "{$domain}_{$this->currentLocale}";
        $translations = $this->translations[$cacheKey] ?? [];

        $value = $this->navigateTranslationTree($translations, $key);
        
        if ($value !== null) {
            return $value;
        }

        // Fallback vers la langue par défaut si différente
        if ($this->currentLocale !== $this->defaultLocale) {
            $fallbackCacheKey = "{$domain}_{$this->defaultLocale}";
            if (!isset($this->translations[$fallbackCacheKey])) {
                $this->loadFallbackTranslations($domain);
            }
            
            $fallbackTranslations = $this->translations[$fallbackCacheKey] ?? [];
            $fallbackValue = $this->navigateTranslationTree($fallbackTranslations, $key);
            
            if ($fallbackValue !== null) {
                return $fallbackValue;
            }
        }

        // Retourner la clé si aucune traduction trouvée
        return $key;
    }

    /**
     * Chargement des traductions de fallback
     */
    private function loadFallbackTranslations(string $domain): void
    {
        $fallbackCacheKey = "{$domain}_{$this->defaultLocale}";
        $fallbackFile = $this->getTranslationFilePath($domain, $this->defaultLocale);
        
        if (file_exists($fallbackFile)) {
            $this->translations[$fallbackCacheKey] = $this->parseTranslationFile($fallbackFile);
        } else {
            $this->translations[$fallbackCacheKey] = [];
        }
    }

    /**
     * Navigation dans l'arbre de traductions avec clés séparées par des points
     */
    private function navigateTranslationTree(array $translations, string $key): ?string
    {
        $keys = explode('.', $key);
        $current = $translations;

        foreach ($keys as $keyPart) {
            if (!is_array($current) || !array_key_exists($keyPart, $current)) {
                return null;
            }
            $current = $current[$keyPart];
        }

        return is_string($current) ? $current : null;
    }

    /**
     * Interpolation des paramètres dans les traductions
     */
    private function interpolateParameters(string $message, array $parameters): string
    {
        if (empty($parameters)) {
            return $message;
        }

        foreach ($parameters as $key => $value) {
            // Support pour les placeholders Symfony avec %
            $placeholderPercent = '%' . $key . '%';
            $message = str_replace($placeholderPercent, (string) $value, $message);
            
            // Support pour les placeholders avec {}
            $placeholderBraces = '{' . $key . '}';
            $message = str_replace($placeholderBraces, (string) $value, $message);
        }

        return $message;
    }

    /**
     * Gestion de fallback en cas d'erreur
     */
    private function getFallbackTranslation(string $key, array $parameters, string $domain): string
    {
        $fallbackKey = "fallback_{$domain}_{$key}_{$this->currentLocale}";
        
        if (isset($this->fallbackCache[$fallbackKey])) {
            return $this->interpolateParameters($this->fallbackCache[$fallbackKey], $parameters);
        }

        // Générer une traduction de fallback intelligente
        $fallback = $this->generateIntelligentFallback($key);
        $this->fallbackCache[$fallbackKey] = $fallback;
        
        return $this->interpolateParameters($fallback, $parameters);
    }

    /**
     * Génération d'un fallback intelligent basé sur la clé
     */
    private function generateIntelligentFallback(string $key): string
    {
        // Extraire la dernière partie de la clé (après le dernier point)
        $parts = explode('.', $key);
        $lastPart = end($parts);
        
        // Convertir snake_case ou camelCase en mots lisibles
        $readable = preg_replace('/[_-]/', ' ', $lastPart);
        $readable = preg_replace('/([a-z])([A-Z])/', '$1 $2', $readable);
        
        return ucfirst(strtolower($readable));
    }

    /**
     * Récupération de la locale actuelle
     */
    public function getLocale(): string
    {
        return $this->currentLocale;
    }

    /**
     * Définition de la locale
     */
    public function setLocale(string $locale): void
    {
        if ($this->isValidLocale($locale)) {
            $this->currentLocale = $locale;
        }
    }

    /**
     * Récupération des locales supportées
     */
    public function getSupportedLocales(): array
    {
        return $this->supportedLocales;
    }

    /**
     * Récupération de la locale par défaut
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    /**
     * Nettoyage du cache
     */
    public function clearCache(): void
    {
        $this->translations = [];
        $this->fallbackCache = [];
    }

    /**
     * Méthode pour récupérer toutes les traductions d'une section
     */
    public function getSection(string $section, string $domain = 'application'): array
    {
        $this->loadTranslations($domain);
        $cacheKey = "{$domain}_{$this->currentLocale}";
        $translations = $this->translations[$cacheKey] ?? [];
        
        return $this->navigateTranslationTree($translations, $section) ?? [];
    }

    /**
     * Vérification de l'existence d'une clé de traduction
     */
    public function hasTranslation(string $key, string $domain = 'application'): bool
    {
        $this->loadTranslations($domain);
        $cacheKey = "{$domain}_{$this->currentLocale}";
        $translations = $this->translations[$cacheKey] ?? [];
        
        return $this->navigateTranslationTree($translations, $key) !== null;
    }

    /**
     * Méthode alias pour compatibilité avec les templates existants
     * Alias pour trans() - utilisée dans les templates Twig
     */
    public function tp(string $key, array $parameters = [], string $domain = 'application'): string
    {
        return $this->trans($key, $parameters, $domain);
    }

    /**
     * Traduction avec injection automatique des variables de l'entreprise
     */
    public function tpWithCompany(string $key, array $parameters = [], string $domain = 'application'): string
    {
        // Injection automatique des variables de l'entreprise
        $companyVariables = [
            'company_name' => $this->companySettingsService->getCompanyName(),
            'company_address' => $this->companySettingsService->getAddress(),
            'company_phone' => $this->companySettingsService->getPhone(),
            'company_email' => $this->companySettingsService->getEmail(),
            'company_website' => $this->companySettingsService->getWebsite(),
        ];

        // Fusion avec les paramètres existants (les paramètres fournis prennent priorité)
        $parameters = array_merge($companyVariables, $parameters);

        return $this->trans($key, $parameters, $domain);
    }

    /**
     * Navigation dans l'arbre de traductions pour récupérer un tableau/section complète
     */
    private function navigateTranslationArray(array $translations, string $key): ?array
    {
        $keys = explode('.', $key);
        $current = $translations;

        foreach ($keys as $keyPart) {
            if (!is_array($current) || !array_key_exists($keyPart, $current)) {
                return null;
            }
            $current = $current[$keyPart];
        }

        return is_array($current) ? $current : null;
    }

    /**
     * Récupère une section de traductions sous forme de tableau
     */
    public function getSectionArray(string $section, string $domain = 'application'): array
    {
        $this->loadTranslations($domain);
        $cacheKey = "{$domain}_{$this->currentLocale}";
        $translations = $this->translations[$cacheKey] ?? [];
        
        return $this->navigateTranslationArray($translations, $section) ?? [];
    }
}
