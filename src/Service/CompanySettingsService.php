<?php

namespace App\Service;

use App\Entity\CompanySettings;
use App\Repository\CompanySettingsRepository;

class CompanySettingsService
{
    private ?CompanySettings $settings = null;

    public function __construct(
        private CompanySettingsRepository $companySettingsRepository
    ) {
    }

    /**
     * Récupère les paramètres de l'entreprise en cache
     */
    public function getCompanySettings(): ?CompanySettings
    {
        if ($this->settings === null) {
            $this->settings = $this->companySettingsRepository->getCompanySettings();
        }

        return $this->settings;
    }

    /**
     * Force le rechargement des paramètres
     */
    public function refreshCompanySettings(): ?CompanySettings
    {
        $this->settings = null;
        return $this->getCompanySettings();
    }

    /**
     * Raccourcis pour les propriétés les plus utilisées
     */
    public function getCompanyName(): ?string
    {
        return $this->getCompanySettings()?->getCompanyName();
    }

    public function getPhone(): ?string
    {
        return $this->getCompanySettings()?->getPhone();
    }

    public function getEmail(): ?string
    {
        return $this->getCompanySettings()?->getEmail();
    }

    public function getAddress(): ?string
    {
        return $this->getCompanySettings()?->getAddress();
    }

    public function getWebsite(): ?string
    {
        return $this->getCompanySettings()?->getWebsite();
    }
}