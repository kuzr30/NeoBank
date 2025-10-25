<?php

namespace App\Service;

use Locale;
use Symfony\Component\Intl\Countries;

/**
 * Service pour la traduction des codes pays en noms complets
 */
class CountryTranslationService
{
    /**
     * Traduit un code pays (ISO 3166-1 alpha-2) en nom complet selon la locale
     */
    public function getCountryName(string $countryCode, string $locale = 'fr'): string
    {
        try {
            // Vérifier que l'extension intl est disponible
            if (!extension_loaded('intl')) {
                return $this->getFallbackCountryName($countryCode);
            }

            // Normaliser le code pays en majuscules
            $countryCode = strtoupper($countryCode);

            // Utiliser Symfony Intl pour obtenir le nom du pays
            $countryName = Countries::getName($countryCode, $locale);
            
            return $countryName ?? $this->getFallbackCountryName($countryCode);
            
        } catch (\Exception $e) {
            // En cas d'erreur, utiliser le fallback
            return $this->getFallbackCountryName($countryCode);
        }
    }

    /**
     * Obtient une liste de tous les pays traduits selon la locale
     */
    public function getAllCountries(string $locale = 'fr'): array
    {
        try {
            if (!extension_loaded('intl')) {
                return $this->getFallbackCountries();
            }

            return Countries::getNames($locale);
            
        } catch (\Exception $e) {
            return $this->getFallbackCountries();
        }
    }

    /**
     * Vérifie si un code pays est valide
     */
    public function isValidCountryCode(string $countryCode): bool
    {
        try {
            $countryCode = strtoupper($countryCode);
            return Countries::exists($countryCode);
        } catch (\Exception $e) {
            return array_key_exists($countryCode, $this->getFallbackCountries());
        }
    }

    /**
     * Fallback pour les codes pays les plus courants quand intl n'est pas disponible
     */
    private function getFallbackCountryName(string $countryCode): string
    {
        $fallbackCountries = $this->getFallbackCountries();
        return $fallbackCountries[strtoupper($countryCode)] ?? $countryCode;
    }

    /**
     * Liste de fallback des pays les plus courants
     */
    private function getFallbackCountries(): array
    {
        return [
            'FR' => 'France',
            'DE' => 'Allemagne',
            'ES' => 'Espagne',
            'IT' => 'Italie',
            'BE' => 'Belgique',
            'NL' => 'Pays-Bas',
            'LU' => 'Luxembourg',
            'CH' => 'Suisse',
            'AT' => 'Autriche',
            'PT' => 'Portugal',
            'GB' => 'Royaume-Uni',
            'US' => 'États-Unis',
            'CA' => 'Canada',
            'PK' => 'Pakistan',
            'IN' => 'Inde',
            'CN' => 'Chine',
            'JP' => 'Japon',
            'BR' => 'Brésil',
            'MX' => 'Mexique',
            'RU' => 'Russie',
            'AU' => 'Australie',
            'ZA' => 'Afrique du Sud',
            'EG' => 'Égypte',
            'MA' => 'Maroc',
            'TN' => 'Tunisie',
            'DZ' => 'Algérie',
            'NG' => 'Nigeria',
            'KE' => 'Kenya'
        ];
    }
}