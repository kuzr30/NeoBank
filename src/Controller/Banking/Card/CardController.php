<?php

namespace App\Controller\Banking\Card;

use App\Entity\User;
use App\Repository\AccountRepository;
use App\Service\CardSubscriptionService;
use App\Service\ProfessionalTranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour la gestion des cartes bancaires
 */
#[Route([
    'fr' => '/{_locale}/banking/cartes',
    'nl' => '/{_locale}/banking/kaarten', 
    'en' => '/{_locale}/banking/cards',
    'de' => '/{_locale}/banking/karten',
    'es' => '/{_locale}/banking/tarjetas'
], name: 'app_banking_card_', requirements: ['_locale' => 'fr|nl|de|en|es'])]
#[IsGranted('ROLE_CLIENT')]
class CardController extends AbstractController
{
    public function __construct(
        private CardSubscriptionService $cardSubscriptionService,
        private AccountRepository $accountRepository,
        private ProfessionalTranslationService $translationService
    ) {}

    /**
     * Page principale des cartes - redirige vers le dashboard avec l'onglet cartes actif
     */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }
        
        // Rediriger vers le dashboard avec l'onglet cartes actif
        return $this->redirectToRoute('banking_dashboard', [
            '_locale' => $request->getLocale(),
            'activeTab' => 'cartes'
        ]);
    }

    /**
     * Vérification de l'accès KYC
     */
    private function checkKycAccess(): ?Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user->isKycApproved()) {
            $this->addFlash('warning', $this->translationService->tp('card_controllers.flash.kyc_required', [], 'card_controllers'));
            return $this->redirectToRoute('app_kyc_index');
        }

        return null;
    }
}
