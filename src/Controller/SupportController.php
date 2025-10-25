<?php

namespace App\Controller;

use App\Service\CompanySettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{_locale}', name: 'support_', requirements: ['_locale' => 'fr|nl|de|en|es'])]
class SupportController extends AbstractController
{
    public function __construct(
        private CompanySettingsService $companySettingsService
    ) {
    }
    #[Route([
        'fr' => '/aide/centre-aide',
        'nl' => '/hulp/helpcentrum',
        'en' => '/help/help-center',
        'de' => '/hilfe/hilfezentrum',
        'es' => '/ayuda/centro-ayuda'
    ], name: 'help_center')]
    public function helpCenter(): Response
    {
        $companySettings = $this->companySettingsService->getCompanySettings();
        
        return $this->render('support/help_center.html.twig', [
            'companySettings' => $companySettings
        ]);
    }

    #[Route([
        'fr' => '/contact',
        'nl' => '/contact',
        'en' => '/contact',
        'de' => '/kontakt',
        'es' => '/contacto'
    ], name: 'contact')]
    public function contact(): Response
    {
        $companySettings = $this->companySettingsService->getCompanySettings();
        
        return $this->render('support/contact.html.twig', [
            'companySettings' => $companySettings
        ]);
    }

    #[Route([
        'fr' => '/agences',
        'nl' => '/kantoren',
        'en' => '/branches',
        'de' => '/filialen',
        'es' => '/sucursales'
    ], name: 'branches')]
    public function branches(): Response
    {
        $companySettings = $this->companySettingsService->getCompanySettings();
        
        return $this->render('support/branches.html.twig', [
            'companySettings' => $companySettings
        ]);
    }

    #[Route([
        'fr' => '/faq',
        'nl' => '/veelgestelde-vragen',
        'en' => '/faq',
        'de' => '/haeufige-fragen',
        'es' => '/preguntas-frecuentes'
    ], name: 'faq')]
    public function faq(): Response
    {
        $companySettings = $this->companySettingsService->getCompanySettings();
        
        return $this->render('support/faq.html.twig', [
            'companySettings' => $companySettings
        ]);
    }

    #[Route([
        'fr' => '/securite',
        'nl' => '/beveiliging',
        'en' => '/security',
        'de' => '/sicherheit',
        'es' => '/seguridad'
    ], name: 'security')]
    public function security(): Response
    {
        $companySettings = $this->companySettingsService->getCompanySettings();
        
        return $this->render('support/security.html.twig', [
            'companySettings' => $companySettings
        ]);
    }

    #[Route([
        'fr' => '/reclamations',
        'nl' => '/klachten',
        'en' => '/complaints',
        'de' => '/beschwerden',
        'es' => '/reclamaciones'
    ], name: 'complaints')]
    public function complaints(): Response
    {
        $companySettings = $this->companySettingsService->getCompanySettings();
        
        return $this->render('support/complaints.html.twig', [
            'companySettings' => $companySettings
        ]);
    }
}