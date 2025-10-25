<?php

namespace App\Controller;

use App\Service\ProfessionalTranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route([
    'fr' => '/{_locale}/mobile-banking',
    'nl' => '/{_locale}/mobiel-bankieren',
    'en' => '/{_locale}/mobile-banking',
    'de' => '/{_locale}/mobiles-banking',
    'es' => '/{_locale}/banca-movil'
], requirements: ['_locale' => 'fr|nl|de|en|es'])]
class BankingServicesController extends AbstractController
{
    public function __construct(
        private ProfessionalTranslationService $translationService
    ) {
    }
    
    #[Route([
        'fr' => '/application-mobile',
        'nl' => '/mobiele-app',
        'en' => '/mobile-app',
        'de' => '/mobile-app',
        'es' => '/aplicacion-movil'
    ], name: 'banking_mobile_app', requirements: ['_locale' => 'fr|nl|de|en|es'], methods: ['GET'])]
    public function mobileApp(): Response
    {
        return $this->render('banking_services/mobile_app.html.twig', [
            'page_title' => $this->translationService->tp('mobile_app.meta.mobile_app.title', [], 'mobile_app'),
            'meta_description' => $this->translationService->tp('mobile_app.meta.mobile_app.description', [], 'mobile_app'),
        ]);
    }

    #[Route([
        'fr' => '/carte-credit',
        'nl' => '/kredietkaart',
        'en' => '/credit-card',
        'de' => '/kreditkarte',
        'es' => '/tarjeta-credito'
    ], name: 'banking_credit_card', requirements: ['_locale' => 'fr|nl|de|en|es'], methods: ['GET'])]
    public function creditCard(): Response
    {
        return $this->render('banking_services/credit_card.html.twig', [
            'page_title' => $this->translationService->tp('banking_cards.meta.credit_card.title', [], 'banking_cards'),
            'meta_description' => $this->translationService->tp('banking_cards.meta.credit_card.description', [], 'banking_cards'),
        ]);
    }

}
