<?php

namespace App\Service;

use App\Repository\CompanySettingsRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class CompanySettingsGlobalService extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private CompanySettingsRepository $companySettingsRepository
    ) {}

    public function getGlobals(): array
    {
        $companySettings = $this->companySettingsRepository->getCompanySettings();
        
        return [
            'company_name' => $companySettings?->getCompanyName() ?? 'SEDEF BANK',
            'company_email' => $companySettings?->getEmail() ?? 'contact@sedefbank.fr',
            'company_phone' => $companySettings?->getPhone() ?? '+33 1 23 45 67 89',
            'company_address' => $companySettings?->getAddress() ?? '3 Rue du Commandant Cousteau, 91300 Massy',
            'company_website' => $companySettings?->getWebsite() ?? 'www.sedefbank.fr',
        ];
    }
}