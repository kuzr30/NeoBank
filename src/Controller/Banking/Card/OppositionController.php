<?php

namespace App\Controller\Banking\Card;

use App\Entity\Card;
use App\Entity\CardOpposition;
use App\Form\CardOppositionType;
use App\Service\CardOppositionService;
use App\Service\ProfessionalTranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour la gestion des oppositions de cartes bancaires
 * Interface utilisateur pour déclarer et suivre les oppositions
 */
#[Route([
    'fr' => '/{_locale}/banking/cartes/opposition',
    'nl' => '/{_locale}/banking/kaarten/blokkering', 
    'en' => '/{_locale}/banking/cards/opposition',
    'de' => '/{_locale}/banking/karten/sperrung',
    'es' => '/{_locale}/banking/tarjetas/oposicion'
], name: 'app_card_opposition_', requirements: ['_locale' => 'fr|nl|de|en|es'])]
#[IsGranted('ROLE_CLIENT')]
class OppositionController extends AbstractController
{
    public function __construct(
        private CardOppositionService $oppositionService,                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           
        private EntityManagerInterface $entityManager,                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              
        private ProfessionalTranslationService $translationService
    ) {}

    /**
     * Liste des cartes de l'utilisateur pour faire opposition
     */                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        
        // Récupérer les cartes actives de l'utilisateur
        $activeCards = $this->entityManager->getRepository(Card::class)
            ->findBy([
                'user' => $user, 
                'status' => ['active'] // Seules les cartes actives peuvent faire l'objet d'une opposition
            ]);

        // Récupérer l'historique des oppositions
        $oppositionHistory = $this->oppositionService->getUserOppositionHistory($user);

        return $this->render('banking/cards/opposition/index.html.twig', [
            'active_cards' => $activeCards,
            'opposition_history' => $oppositionHistory,
        ]);
    }

    /**
     * Formulaire de déclaration d'opposition
     */
    #[Route([
        'fr' => '/carte/{id}',
        'nl' => '/kaart/{id}',
        'en' => '/card/{id}',
        'de' => '/karte/{id}',
        'es' => '/tarjeta/{id}'
    ], name: 'create', methods: ['GET', 'POST'])]
    public function createOpposition(Card $card, Request $request): Response
    {
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est propriétaire de la carte
        if ($card->getUser() !== $user) {
            throw $this->createAccessDeniedException($this->translationService->tp('card_controllers.card_controller.access_denied.card', [], 'card_controllers'));
        }

        // Vérifier que la carte peut faire l'objet d'une opposition
        if ($card->getStatus() !== 'active') {
            $this->addFlash('error', $this->translationService->tp('card_controllers.opposition.flash.only_active_cards', [], 'card_controllers'));
            return $this->redirectToRoute('app_card_opposition_index');
        }

        // Vérifier qu'il n'y a pas déjà une opposition en cours
        if ($this->oppositionService->hasActiveOpposition($card)) {
            $this->addFlash('error', $this->translationService->tp('card_controllers.opposition.flash.already_in_progress', [], 'card_controllers'));
            return $this->redirectToRoute('app_card_opposition_index');
        }

        $opposition = new CardOpposition();
        $opposition->setCard($card);
        $opposition->setRequestedBy($user);
        
        $form = $this->createForm(CardOppositionType::class, $opposition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->oppositionService->createOpposition(
                    $card,
                    $user,
                    $opposition->getReason(),
                    $opposition->getDescription()
                );

                $this->addFlash('success', $this->translationService->tp('card_controllers.opposition.flash.success_created', [], 'card_controllers'));
                
                return $this->redirectToRoute('app_card_opposition_index');
                
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translationService->tp('card_controllers.opposition.flash.error_processing', [], 'card_controllers'));
            }
        }

        return $this->render('banking/cards/opposition/create.html.twig', [
            'form' => $form->createView(),
            'card' => $card,
        ]);
    }

    /**
     * Détails d'une opposition
     */
    #[Route([
        'fr' => '/opposition/{id}',
        'nl' => '/blokkering/{id}',
        'en' => '/opposition/{id}',
        'de' => '/sperrung/{id}',
        'es' => '/oposicion/{id}'
    ], name: 'show', methods: ['GET'])]
    public function show(CardOpposition $opposition): Response
    {
        // Vérifier que l'utilisateur est propriétaire de l'opposition
        if ($opposition->getRequestedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException($this->translationService->tp('card_controllers.card_controller.access_denied.opposition', [], 'card_controllers'));
        }

        return $this->render('banking/cards/opposition/detail.html.twig', [
            'opposition' => $opposition,
        ]);
    }

    /**
     * Page d'urgence pour opposition immédiate
     */
    #[Route([
        'fr' => '/urgence',
        'nl' => '/noodgeval',
        'en' => '/emergency',
        'de' => '/notfall',
        'es' => '/emergencia'
    ], name: 'emergency', methods: ['GET', 'POST'])]
    public function emergency(Request $request): Response
    {
        $user = $this->getUser();
        
        if ($request->isMethod('POST')) {
            $cardId = $request->request->get('card_id');
            $reason = $request->request->get('reason', 'lost');
            
            if (!$cardId) {
                $this->addFlash('error', $this->translationService->tp('card_controllers.opposition.flash.select_card_required', [], 'card_controllers'));
                return $this->redirectToRoute('app_card_opposition_emergency');
            }

            $card = $this->entityManager->getRepository(Card::class)->find($cardId);
            
            if (!$card || $card->getUser() !== $user) {
                $this->addFlash('error', $this->translationService->tp('card_controllers.opposition.flash.card_not_found', [], 'card_controllers'));
                return $this->redirectToRoute('app_card_opposition_emergency');
            }

            try {
                $this->oppositionService->createOpposition(
                    $card,
                    $user,
                    $reason,
                    $this->translationService->tp('card_controllers.opposition.emergency_description', [], 'card_controllers')
                );

                $this->addFlash('success', $this->translationService->tp('card_controllers.opposition.flash.emergency_success', ['reference' => $card->getCardNumber()], 'card_controllers'));
                
                return $this->redirectToRoute('app_card_opposition_index');
                
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translationService->tp('card_controllers.opposition.flash.emergency_error', [], 'card_controllers'));
            }
        }
        
        // Récupérer les cartes actives pour sélection rapide
        $activeCards = $this->entityManager->getRepository(Card::class)
            ->findBy(['user' => $user, 'status' => 'active']);

        return $this->render('banking/cards/opposition/emergency.html.twig', [
            'active_cards' => $activeCards,
        ]);
    }

    /**
     * Vérification du statut d'une opposition (AJAX)
     */
    #[Route([
        'fr' => '/opposition/{id}/statut',
        'nl' => '/blokkering/{id}/status',
        'en' => '/opposition/{id}/status',
        'de' => '/sperrung/{id}/status',
        'es' => '/oposicion/{id}/estado'
    ], name: 'status', methods: ['GET'])]
    public function getOppositionStatus(CardOpposition $opposition): Response
    {
        // Vérifier que l'utilisateur est propriétaire de l'opposition
        if ($opposition->getRequestedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->json([
            'status' => $opposition->getStatus(),
            'reference' => $opposition->getReference(),
            'processed_at' => $opposition->getProcessedAt()?->format('Y-m-d H:i:s'),
            'replacement_subscription' => $opposition->getReplacementSubscription() ? [
                'id' => $opposition->getReplacementSubscription()->getId(),
                'status' => $opposition->getReplacementSubscription()->getStatus(),
                'reference' => $opposition->getReplacementSubscription()->getReference()
            ] : null
        ]);
    }
}
