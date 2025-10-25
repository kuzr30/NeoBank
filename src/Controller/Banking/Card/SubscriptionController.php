<?php

namespace App\Controller\Banking\Card;

use App\Entity\Account;
use App\Entity\CardSubscription;
use App\Entity\User;
use App\Enum\CardConditionEnum;
use App\Form\CardSubscriptionType;
use App\Repository\AccountRepository;
use App\Service\CardSubscriptionService;
use App\Service\ProfessionalTranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour la gestion des souscriptions de cartes bancaires
 */
#[Route([
    'fr' => '/{_locale}/banking/cartes/souscrire',
    'nl' => '/{_locale}/banking/kaarten/abonneren', 
    'en' => '/{_locale}/banking/cards/subscribe',
    'de' => '/{_locale}/banking/karten/abonnieren',
    'es' => '/{_locale}/banking/tarjetas/suscribir'
], name: 'app_banking_card_subscription_', requirements: ['_locale' => 'fr|nl|de|en|es'])]
#[IsGranted('ROLE_CLIENT')]
class SubscriptionController extends AbstractController
{
    public function __construct(
        private CardSubscriptionService $subscriptionService,
        private EntityManagerInterface $entityManager,
        private AccountRepository $accountRepository,
        private ProfessionalTranslationService $translationService
    ) {}

    /**
     * Page de souscription pour carte Classic
     */
    #[Route([
        'fr' => '/classic',
        'nl' => '/classic',
        'en' => '/classic',
        'de' => '/classic',
        'es' => '/classic'
    ], name: 'classic', methods: ['GET', 'POST'])]
    public function subscribeClassic(Request $request): Response
    {
        $cardCondition = CardConditionEnum::CLASSIC;
        
        return $this->handleSubscription($request, $cardCondition, [
            'title' => $this->translationService->tp('banking_card_subscription_controller.card_info.classic.title', [], 'banking_card_subscription_controller'),
            'description' => $this->translationService->tp('banking_card_subscription_controller.card_info.classic.description', [], 'banking_card_subscription_controller'),
            'price' => $this->translationService->tp('banking_card_subscription_controller.card_info.classic.price', [], 'banking_card_subscription_controller'),
            'conditions' => $this->translationService->trans($cardCondition->getConditionsKey(), [], 'CardConditionEnum'),
            'limits' => $this->getTranslatedLimits($cardCondition),
            'features' => $this->getTranslatedFeatures($cardCondition)
        ]);
    }

    /**
     * Page de souscription pour carte Gold
     */
    #[Route([
        'fr' => '/gold',
        'nl' => '/gold',
        'en' => '/gold',
        'de' => '/gold',
        'es' => '/gold'
    ], name: 'gold', methods: ['GET', 'POST'])]
    public function subscribeGold(Request $request): Response
    {
        $cardCondition = CardConditionEnum::GOLD;
        
        return $this->handleSubscription($request, $cardCondition, [
            'title' => $this->translationService->tp('banking_card_subscription_controller.card_info.gold.title', [], 'banking_card_subscription_controller'),
            'description' => $this->translationService->tp('banking_card_subscription_controller.card_info.gold.description', [], 'banking_card_subscription_controller'),
            'price' => $this->translationService->tp('banking_card_subscription_controller.card_info.gold.price', [], 'banking_card_subscription_controller'),
            'conditions' => $this->translationService->trans($cardCondition->getConditionsKey(), [], 'CardConditionEnum'),
            'limits' => $this->getTranslatedLimits($cardCondition),
            'features' => $this->getTranslatedFeatures($cardCondition)
        ]);
    }

    /**
     * Page de souscription pour carte Platinum
     */
    #[Route([
        'fr' => '/platinum',
        'nl' => '/platinum',
        'en' => '/platinum',
        'de' => '/platinum',
        'es' => '/platinum'
    ], name: 'platinum', methods: ['GET', 'POST'])]
    public function subscribePlatinum(Request $request): Response
    {
        $cardCondition = CardConditionEnum::PLATINUM;
        
        return $this->handleSubscription($request, $cardCondition, [
            'title' => $this->translationService->tp('banking_card_subscription_controller.card_info.platinum.title', [], 'banking_card_subscription_controller'),
            'description' => $this->translationService->tp('banking_card_subscription_controller.card_info.platinum.description', [], 'banking_card_subscription_controller'),
            'price' => $this->translationService->tp('banking_card_subscription_controller.card_info.platinum.price', [], 'banking_card_subscription_controller'),
            'conditions' => $this->translationService->trans($cardCondition->getConditionsKey(), [], 'CardConditionEnum'),
            'limits' => $this->getTranslatedLimits($cardCondition),
            'features' => $this->getTranslatedFeatures($cardCondition)
        ]);
    }

    /**
     * Gestion commune de la souscription
     */
    private function handleSubscription(Request $request, CardConditionEnum $cardCondition, array $cardInfo): Response
    {
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }

        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur n'a pas déjà une carte ou souscription
        $existingCardOrSubscription = $this->subscriptionService->getUserCardOrSubscription($user);
        if ($existingCardOrSubscription['type'] !== 'none') {
            $this->addFlash('warning', $this->translationService->tp('card_controllers.subscription.flash.already_has_card', [], 'card_controllers'));
            return $this->redirectToRoute('app_banking_card_index');
        }

        // Récupérer les comptes actifs de l'utilisateur
        $userAccounts = $this->accountRepository->findActiveByUser($user);

        if (empty($userAccounts)) {
            $this->addFlash('error', $this->translationService->tp('card_controllers.subscription.flash.no_active_account', [], 'card_controllers'));
            return $this->redirectToRoute('app_banking_card_index');
        }

        // Récupérer la marque depuis l'URL (visa par défaut)
        $cardBrand = $request->query->get('brand', 'visa');
        
        // Si c'est une requête POST, traiter la demande directement
        if ($request->isMethod('POST')) {
            try {
                // Utiliser le premier compte actif par défaut
                $defaultAccount = $userAccounts[0];
                
                $this->subscriptionService->createSubscription(
                    $user,
                    $defaultAccount,
                    $cardCondition->value,
                    $cardBrand,
                    $this->translationService->tp('card_controllers.subscription.default_description', [], 'card_controllers'),
                    $cardCondition->value // Niveau de carte
                );

                $this->addFlash('success', $this->translationService->tp('card_controllers.subscription.flash.success_created', [], 'card_controllers'));
                
                return $this->redirectToRoute('app_banking_card_index');
                
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translationService->tp('card_controllers.subscription.flash.error_processing', [], 'card_controllers'));
            }
        }

        // Récupérer les données nécessaires pour le dashboard
        $accounts = $this->accountRepository->findActiveByUser($user);
        
        // Afficher la page dans la structure complète du dashboard avec onglet card-subscription
        return $this->render('banking/dashboard.html.twig', [
            'cardType' => $cardCondition->value,
            'cardCondition' => $cardCondition,
            'cardBrand' => $cardBrand,
            'cardInfo' => $cardInfo,
            'userAccount' => $userAccounts[0], // Premier compte actif
            'activeTab' => 'card-subscription',
            'user' => $user,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Détails d'une souscription
     */
    #[Route([
        'fr' => '/{id}',
        'nl' => '/{id}',
        'en' => '/{id}',
        'de' => '/{id}',
        'es' => '/{id}'
    ], name: 'show', methods: ['GET'])]
    public function show(CardSubscription $subscription): Response
    {
        // Vérifier que l'utilisateur est propriétaire de la souscription
        if ($subscription->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException($this->translationService->tp('card_controllers.card_controller.access_denied.subscription', [], 'card_controllers'));
        }

        return $this->render('banking/cards/subscription/detail.html.twig', [
            'subscription' => $subscription,
        ]);
    }

    /**
     * Annulation d'une souscription en cours
     */
    #[Route([
        'fr' => '/{id}/annuler',
        'nl' => '/{id}/annuleren',
        'en' => '/{id}/cancel',
        'de' => '/{id}/stornieren',
        'es' => '/{id}/cancelar'
    ], name: 'cancel', methods: ['POST'])]
    public function cancelSubscription(CardSubscription $subscription, Request $request): Response
    {
        // Vérifier que l'utilisateur est propriétaire de la souscription
        if ($subscription->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException($this->translationService->tp('card_controllers.card_controller.access_denied.subscription', [], 'card_controllers'));
        }

        // Vérifier que la souscription peut être annulée
        if ($subscription->getStatus() !== 'pending') {
            $this->addFlash('error', $this->translationService->tp('card_controllers.subscription.flash.only_pending_can_cancel', [], 'card_controllers'));
            return $this->redirectToRoute('app_banking_card_subscription_show', ['id' => $subscription->getId()]);
        }

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('cancel_subscription_' . $subscription->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', $this->translationService->tp('card_controllers.subscription.flash.invalid_csrf_token', [], 'card_controllers'));
            return $this->redirectToRoute('app_banking_card_subscription_show', ['id' => $subscription->getId()]);
        }

        try {
            $subscription->setStatus('cancelled');
            $subscription->setCancelledAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            $this->addFlash('success', $this->translationService->tp('card_controllers.subscription.flash.success_cancelled', [], 'card_controllers'));
            
        } catch (\Exception $e) {
            $this->addFlash('error', $this->translationService->tp('card_controllers.subscription.flash.error_cancelling', [], 'card_controllers'));
        }

        return $this->redirectToRoute('app_banking_card_index');
    }

    /**
     * Vérification du statut d'une souscription (AJAX)
     */
    #[Route([
        'fr' => '/{id}/statut',
        'nl' => '/{id}/status',
        'en' => '/{id}/status',
        'de' => '/{id}/status',
        'es' => '/{id}/estado'
    ], name: 'status', methods: ['GET'])]
    public function getSubscriptionStatus(CardSubscription $subscription): Response
    {
        // Vérifier que l'utilisateur est propriétaire de la souscription
        if ($subscription->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->json([
            'status' => $subscription->getStatus(),
            'updated_at' => $subscription->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'reference' => $subscription->getReference(),
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

    /**
     * Récupère les features traduites sous forme de tableau
     */
    private function getTranslatedFeatures(CardConditionEnum $cardCondition): array
    {
        return $this->translationService->getSectionArray(
            'banking_card_subscription_controller.card_info.' . $cardCondition->value . '.features', 
            'banking_card_subscription_controller'
        );
    }

    /**
     * Récupère les limits traduites sous forme de tableau
     */
    private function getTranslatedLimits(CardConditionEnum $cardCondition): array
    {
        return $this->translationService->getSectionArray(
            'banking_card_subscription_controller.card_info.' . $cardCondition->value . '.limits', 
            'banking_card_subscription_controller'
        );
    }
}
