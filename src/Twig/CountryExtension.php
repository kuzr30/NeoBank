<?php

namespace App\Twig;

use App\Service\CountryTranslationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CountryExtension extends AbstractExtension
{
    public function __construct(
        private CountryTranslationService $countryTranslationService
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('country_name', [$this, 'getCountryName']),
            new TwigFunction('all_countries', [$this, 'getAllCountries']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('country', [$this, 'getCountryName']),
        ];
    }

    /**
     * Obtient le nom d'un pays à partir de son code ISO
     */
    public function getCountryName(string $countryCode, string $locale = 'fr'): string
    {
        return $this->countryTranslationService->getCountryName($countryCode, $locale);
    }

    /**
     * Obtient tous les pays traduits
     */
    public function getAllCountries(string $locale = 'fr'): array
    {
        return $this->countryTranslationService->getAllCountries($locale);
    }
}