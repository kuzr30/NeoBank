<?php

namespace App\Twig;

use App\Service\CompanySettingsService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CompanyVariablesExtension extends AbstractExtension
{
    public function __construct(
        private CompanySettingsService $companySettingsService,
        private TranslatorInterface $translator
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('tp_with_company', [$this, 'translateWithCompanyVariables']),
            new TwigFunction('company_phone', [$this, 'getCompanyPhone']),
            new TwigFunction('company_email', [$this, 'getCompanyEmail']),
            new TwigFunction('company_address', [$this, 'getCompanyAddress']),
            new TwigFunction('company_name', [$this, 'getCompanyName']),
            new TwigFunction('company_website', [$this, 'getCompanyWebsite']),
            new TwigFunction('company_logo_base64', [$this, 'getCompanyLogoBase64']),
        ];
    }

    /**
     * Traduction avec injection automatique des variables de l'entreprise
     */
    public function translateWithCompanyVariables(string $key, array $parameters = [], string $domain = null, string $locale = null): string
    {
        // Injection automatique des variables de l'entreprise avec valeurs par défaut
        $companyVariables = [
            'company_name' => $this->companySettingsService->getCompanyName() ?? 'SEDEF BANK',
            'company_phone' => $this->companySettingsService->getPhone() ?? '+33 1 23 45 67 89',
            'company_email' => $this->companySettingsService->getEmail() ?? 'contact@sedefbank.com',
            'company_address' => $this->companySettingsService->getAddress() ?? '123 Avenue des Finances, 75001 Paris, France',
            'company_website' => $this->companySettingsService->getWebsite() ?? 'www.sedefbank.com',
        ];

        // Fusion avec les paramètres existants
        $parameters = array_merge($companyVariables, $parameters);

        // Récupération de la traduction avec remplacement des paramètres
        return $this->translator->trans($key, $parameters, $domain, $locale);
    }

    /**
     * Raccourcis pour les propriétés de l'entreprise
     */
    public function getCompanyPhone(): string
    {
        return $this->companySettingsService->getPhone() ?? '+33 1 23 45 67 89';
    }

    public function getCompanyEmail(): string
    {
        return $this->companySettingsService->getEmail() ?? 'contact@sedefbank.com';
    }

    public function getCompanyAddress(): string
    {
        return $this->companySettingsService->getAddress() ?? '123 Avenue des Finances, 75001 Paris, France';
    }

    public function getCompanyName(): string
    {
        return $this->companySettingsService->getCompanyName() ?? 'SEDEF BANK';
    }

    public function getCompanyWebsite(): string
    {
        return $this->companySettingsService->getWebsite() ?? 'www.sedefbank.com';
    }

    public function getCompanyLogoBase64(): ?string
    {
        return $this->companySettingsService->getCompanySettings()?->getLogoBase64();
    }
}