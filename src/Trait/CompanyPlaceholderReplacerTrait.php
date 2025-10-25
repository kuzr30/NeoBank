<?php

namespace App\Trait;

use App\Service\CompanySettingsService;

/**
 * Trait pour remplacer les placeholders d'entreprise dans les textes
 */
trait CompanyPlaceholderReplacerTrait
{
    private CompanySettingsService $companySettingsService;

    /**
     * Remplace les placeholders d'entreprise dans une chaîne
     */
    private function replaceCompanyPlaceholders(string $text): string
    {
        $replacements = [
            '%company_name%' => $this->companySettingsService->getCompanyName() ?? 'SEDEF BANK',
            '%company_phone%' => $this->companySettingsService->getPhone() ?? '+33 1 23 45 67 89',
            '%company_email%' => $this->companySettingsService->getEmail() ?? 'contact@sedefbank.com',
            '%company_address%' => $this->companySettingsService->getAddress() ?? '3 Rue du Commandant Cousteau, 91300 Massy',
            '%company_website%' => $this->companySettingsService->getWebsite() ?? 'www.sedefbank.com',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
}
