<?php

namespace App\Controller;

use App\Enum\CreditTypeEnum;
use App\Service\ProfessionalTranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route([
    'fr' => '/{_locale}/offres-credit',
    'nl' => '/{_locale}/krediet-aanbod',
    'en' => '/{_locale}/credit-offers',
    'de' => '/{_locale}/kredit-angebote',
    'es' => '/{_locale}/ofertas-credito'
], requirements: ['_locale' => 'fr|nl|de|en|es'])]
class OurCreditOfferController extends AbstractController
{
    private ProfessionalTranslationService $translationService;

    public function __construct(ProfessionalTranslationService $translationService)
    {
        $this->translationService = $translationService;
    }
    #[Route([
        'fr' => '/',
        'nl' => '/',
        'en' => '/',
        'de' => '/',
        'es' => '/'
    ], name: 'credit_offers_index', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function index(): Response
    {
        // Préparer tous les types de crédit avec leurs informations
        $creditTypes = [];
        foreach (CreditTypeEnum::cases() as $creditType) {
            $creditTypes[] = [
                'enum' => $creditType,
                'value' => $creditType->value,
                'label' => $creditType->getLabel(),
                'description' => $creditType->getDescription(),
                'rate' => $creditType->getRate(),
                'french_label' => $creditType->getFrenchLabel()
            ];
        }
        
        return $this->render('credit_offers/index.html.twig', [
            'page_title' => $this->translationService->trans('credit_page.index.page_title', [], 'credit_page'),
            'page_description' => $this->translationService->trans('credit_page.index.page_description', [], 'credit_page'),
            'credit_types' => $creditTypes
        ]);
    }

    #[Route([
        'fr' => '/credit-personnel',
        'nl' => '/persoonlijke-lening',
        'en' => '/personal-loan',
        'de' => '/privatkredit',
        'es' => '/prestamo-personal'
    ], name: 'credit_offers_personal_loan', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function personalLoan(): Response
    {
        $creditType = CreditTypeEnum::PERSONAL;
        
        return $this->render('credit_offers/personal_loan.html.twig', [
            'page_title' => $this->translationService->trans('personal_loan.page.title', [], 'personal_loan'),
            'page_description' => $this->translationService->trans('personal_loan.page.description', [], 'personal_loan'),
            'credit_type' => $creditType,
            'interest_rate' => $creditType->getRate(),
            'credit_label' => $creditType->getLabel(),
            'credit_description' => $creditType->getDescription()
        ]);
    }

    #[Route([
        'fr' => '/credit-voyage',
        'nl' => '/reisgeld',
        'en' => '/travel-loan',
        'de' => '/reisekredit',
        'es' => '/prestamo-viaje'
    ], name: 'credit_offers_travel_loan', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function travelLoan(): Response
    {
        return $this->render('credit_offers/travel_loan.html.twig', [
            'page_title' => $this->translationService->trans('page_title', [], 'travel-loan'),
            'page_description' => $this->translationService->trans('page_description', [], 'travel-loan')
        ]);
    }

    #[Route([
        'fr' => '/credit-auto',
        'nl' => '/autolening',
        'en' => '/auto-loan',
        'de' => '/autokredit',
        'es' => '/prestamo-auto'
    ], name: 'credit_offers_auto_loan', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function autoLoan(): Response
    {
        $creditType = CreditTypeEnum::AUTO;
        
        return $this->render('credit_offers/auto_loan.html.twig', [
            'page_title' => $this->translationService->trans('auto_loan.page.title', [], 'auto_loan'),
            'page_description' => $this->translationService->trans('auto_loan.page.description', [], 'auto_loan'),
            'credit_type' => $creditType,
            'interest_rate' => $creditType->getRate(),
            'credit_label' => $creditType->getLabel(),
            'credit_description' => $creditType->getDescription()
        ]);
    }

    #[Route([
        'fr' => '/credit-immobilier',
        'nl' => '/hypotheek',
        'en' => '/home-loan',
        'de' => '/immobilienkredit',
        'es' => '/prestamo-vivienda'
    ], name: 'credit_offers_home_loan', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function homeLoan(): Response
    {
        $creditType = CreditTypeEnum::IMMOBILIER;
        
        return $this->render('credit_offers/home_loan.html.twig', [
            'page_title' => $this->translationService->trans('home_loan.page.title', [], 'home_loan'),
            'page_description' => $this->translationService->trans('home_loan.page.description', [], 'home_loan'),
            'credit_type' => $creditType,
            'interest_rate' => $creditType->getRate(),
            'credit_label' => $creditType->getLabel(),
            'credit_description' => $creditType->getDescription()
        ]);
    }

    #[Route([
        'fr' => '/hypothecaire',
        'nl' => '/hypothecair',
        'en' => '/hypothecary',
        'de' => '/hypothekar',
        'es' => '/hipotecario'
    ], name: 'credit_offers_hypothecaire', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function hypothecaire(): Response
    {
        return $this->render('credit_offers/hypothecaire.html.twig', [
            'page_title' => $this->translationService->trans('page_title', [], 'hypothecaire'),
            'page_description' => $this->translationService->trans('page_description', [], 'hypothecaire')
        ]);
    }

    #[Route([
        'fr' => '/travaux',
        'nl' => '/verbouwing',
        'en' => '/renovation',
        'de' => '/renovierung',
        'es' => '/reformas'
    ], name: 'credit_offers_travaux', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function travaux(): Response
    {
        return $this->render('credit_offers/travaux.html.twig', [
            'page_title' => $this->translationService->trans('amelioration_habitat.page.title', [], 'amelioration_habitat'),
            'page_description' => $this->translationService->trans('amelioration_habitat.page.description', [], 'amelioration_habitat')
        ]);
    }

    #[Route([
        'fr' => '/amelioration-habitat',
        'nl' => '/woning-verbetering',
        'en' => '/home-improvement',
        'de' => '/wohnungsverbesserung',
        'es' => '/mejoras-hogar'
    ], name: 'credit_offers_amelioration_habitat', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function ameliorationHabitat(): Response
    {
        return $this->render('credit_offers/amelioration_habitat.html.twig', [
            'page_title' => $this->translationService->trans('amelioration_habitat.page.title', [], 'amelioration_habitat'),
            'page_description' => $this->translationService->trans('amelioration_habitat.page.description', [], 'amelioration_habitat')
        ]);
    }

    #[Route([
        'fr' => '/credit-relais',
        'nl' => '/overbruggingskredit',
        'en' => '/bridge-loan',
        'de' => '/zwischenfinanzierung',
        'es' => '/credito-puente'
    ], name: 'credit_offers_credit_relais', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function creditRelais(): Response
    {
        return $this->render('credit_offers/credit_relais.html.twig', [
            'page_title' => $this->translationService->trans('credit_relais.page.title', [], 'credit_relais'),
            'page_description' => $this->translationService->trans('credit_relais.page.description', [], 'credit_relais')
        ]);
    }

    #[Route([
        'fr' => '/leasing-automobile',
        'nl' => '/auto-lease',
        'en' => '/car-leasing',
        'de' => '/auto-leasing',
        'es' => '/leasing-automovil'
    ], name: 'credit_offers_leasing_automobile', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function leasingAutomobile(): Response
    {
        return $this->render('credit_offers/leasing_automobile.html.twig', [
            'page_title' => $this->translationService->trans('leasing_automobile.page.title', [], 'leasing_automobile'),
            'page_description' => $this->translationService->trans('leasing_automobile.page.description', [], 'leasing_automobile')
        ]);
    }

    #[Route([
        'fr' => '/credit-consommation',
        'nl' => '/consumptie-krediet',
        'en' => '/consumer-credit',
        'de' => '/verbraucherdarlehen',
        'es' => '/credito-consumo'
    ], name: 'credit_offers_credit_consommation', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function creditConsommation(): Response
    {
        return $this->render('credit_offers/credit_consommation.html.twig', [
            'page_title' => $this->translationService->trans('credit_consommation.page.title', [], 'credit_consommation'),
            'page_description' => $this->translationService->trans('credit_consommation.page.description', [], 'credit_consommation')
        ]);
    }

    #[Route([
        'fr' => '/regroupement-credits',
        'nl' => '/krediet-hergroepering',
        'en' => '/debt-consolidation',
        'de' => '/kreditumschuldung',
        'es' => '/reunificacion-creditos'
    ], name: 'credit_offers_regroupement_credits', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function regroupementCredits(): Response
    {
        return $this->render('credit_offers/regroupement_credits.html.twig', [
            'page_title' => $this->translationService->trans('regroupement_credits.page.title', [], 'regroupement_credits'),
            'page_description' => $this->translationService->trans('regroupement_credits.page.description', [], 'regroupement_credits')
        ]);
    }

    #[Route([
        'fr' => '/credit-renouvelable',
        'nl' => '/hernieuwbaar-krediet',
        'en' => '/revolving-credit',
        'de' => '/revolvierende-kredit',
        'es' => '/credito-renovable'
    ], name: 'credit_offers_credit_renouvelable', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function creditRenouvelable(): Response
    {
        return $this->render('credit_offers/credit_renouvelable.html.twig', [
            'page_title' => $this->translationService->trans('credit_renouvelable.page.title', [], 'credit_renouvelable'),
            'page_description' => $this->translationService->trans('credit_renouvelable.page.description', [], 'credit_renouvelable')
        ]);
    }

    #[Route([
        'fr' => '/credit-professionnel',
        'nl' => '/zakelijk-krediet',
        'en' => '/business-loan',
        'de' => '/geschaeftskredit',
        'es' => '/credito-profesional'
    ], name: 'credit_offers_credit_professionnel', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function creditProfessionnel(): Response
    {
        return $this->render('credit_offers/credit_professionnel.html.twig', [
            'page_title' => $this->translationService->trans('credit_professionnel.page.title', [], 'credit_professionnel'),
            'page_description' => $this->translationService->trans('credit_professionnel.page.description', [], 'credit_professionnel')
        ]);
    }

    #[Route([
        'fr' => '/credit-etudiant',
        'nl' => '/studenten-lening',
        'en' => '/student-loan',
        'de' => '/studienkredit',
        'es' => '/credito-estudiante'
    ], name: 'credit_offers_credit_etudiant', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function creditEtudiant(): Response
    {
        return $this->render('credit_offers/credit_etudiant.html.twig', [
            'page_title' => $this->translationService->trans('credit_etudiant.page.title', [], 'credit_etudiant'),
            'page_description' => $this->translationService->trans('credit_etudiant.page.description', [], 'credit_etudiant')
        ]);
    }

    #[Route([
        'fr' => '/microcredit',
        'nl' => '/microkrediet',
        'en' => '/microcredit',
        'de' => '/mikrokredit',
        'es' => '/microcredito'
    ], name: 'credit_offers_microcredit', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function microcredit(): Response
    {
        return $this->render('credit_offers/microcredit.html.twig', [
            'page_title' => $this->translationService->trans('microcredit.page.title', [], 'microcredit'),
            'page_description' => $this->translationService->trans('microcredit.page.description', [], 'microcredit')
        ]);
    }
}
