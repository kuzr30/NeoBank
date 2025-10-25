<?php

namespace App\Controller;

use App\Service\CompanySettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{_locale}', name: 'legal_', requirements: ['_locale' => 'fr|nl|de|en|es'])]
class LegalController extends AbstractController
{
    public function __construct(
        private CompanySettingsService $companySettingsService
    ) {
    }

    #[Route([
        'fr' => '/mentions-legales',
        'nl' => '/juridische-vermeldingen',
        'en' => '/legal-notices',
        'de' => '/rechtliche-hinweise',
        'es' => '/avisos-legales'
    ], name: 'notices')]
    public function legalNotices(): Response
    {
        $companySettings = $this->companySettingsService->getCompanySettings();
        
        return $this->render('legal/legal_notices.html.twig', [
            'companySettings' => $companySettings
        ]);
    }

    #[Route([
        'fr' => '/conditions-generales',
        'nl' => '/algemene-voorwaarden',
        'en' => '/terms-conditions',
        'de' => '/allgemeine-geschaeftsbedingungen',
        'es' => '/terminos-condiciones'
    ], name: 'terms')]
    public function termsConditions(): Response
    {
        $companySettings = $this->companySettingsService->getCompanySettings();
        
        return $this->render('legal/terms_conditions.html.twig', [
            'companySettings' => $companySettings
        ]);
    }

    #[Route([
        'fr' => '/confidentialite',
        'nl' => '/privacy-beleid',
        'en' => '/privacy-policy',
        'de' => '/datenschutz',
        'es' => '/politica-privacidad'
    ], name: 'privacy')]
    public function privacyPolicy(): Response
    {
        $companySettings = $this->companySettingsService->getCompanySettings();
        
        return $this->render('legal/privacy_policy.html.twig', [
            'companySettings' => $companySettings
        ]);
    }

    #[Route([
        'fr' => '/cookies',
        'nl' => '/cookies',
        'en' => '/cookies',
        'de' => '/cookies',
        'es' => '/cookies'
    ], name: 'cookies')]
    public function cookies(): Response
    {
        $companySettings = $this->companySettingsService->getCompanySettings();
        
        return $this->render('legal/cookies.html.twig', [
            'companySettings' => $companySettings
        ]);
    }
}