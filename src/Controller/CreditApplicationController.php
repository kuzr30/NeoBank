<?php

namespace App\Controller;

use App\DTO\CreditApplicationDTO;
use App\Entity\CreditApplication;
use App\Enum\CreditApplicationStatusEnum;
use App\Form\CreditApplication\CreditApplicationStep1Type;
use App\Form\CreditApplication\CreditApplicationStep2Type;
use App\Form\CreditApplication\CreditApplicationStep3Type;
use App\Message\CreditApplicationSubmittedMessage;
use App\Service\CreditApplicationMapperService;
use App\Service\ProfessionalTranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route([
    'fr' => '/{_locale}/ma-demande-de-credit',
    'nl' => '/{_locale}/mijn-kredietaanvraag',
    'en' => '/{_locale}/my-credit-application',
    'de' => '/{_locale}/mein-kreditantrag',
    'es' => '/{_locale}/mi-solicitud-credito'
], requirements: ['_locale' => 'fr|nl|de|en|es'])]
class CreditApplicationController extends AbstractController
{
    private const SESSION_KEY = 'credit_application_dto';
    private const MAX_STEPS = 3;

    public function __construct(
        private readonly CreditApplicationMapperService $mapperService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly MessageBusInterface $messageBus,
        private readonly ProfessionalTranslationService $translationService
    ) {}

    #[Route('/', name: 'credit_application_start', methods: ['GET'])]
    #[Route([
        'fr' => '',
        'nl' => '', 
        'en' => '',
        'de' => '',
        'es' => ''
    ], name: 'credit_application_start_alt', methods: ['GET'])]
    public function start(SessionInterface $session): Response
    {
        // Réinitialiser la session pour une nouvelle demande
        $session->remove(self::SESSION_KEY);
        
        return $this->redirectToRoute('credit_application_step', ['step' => 1]);
    }

    #[Route([
        'fr' => '/etape/{step}',
        'nl' => '/stap/{step}',
        'en' => '/step/{step}',
        'de' => '/schritt/{step}',
        'es' => '/paso/{step}'
    ], name: 'credit_application_step', requirements: ['step' => '\d+', '_locale' => 'fr|nl|de|en|es'], methods: ['GET', 'POST'])]
    public function step(int $step, Request $request, SessionInterface $session): Response
    {
        // Vérifier que l'étape est valide
        if ($step < 1 || $step > self::MAX_STEPS) {
            throw $this->createNotFoundException(
                $this->translationService->tp('exceptions.step_not_found', [], 'credit_application_controller')
            );
        }

        // Récupérer ou créer le DTO depuis la session
        $dto = $this->getOrCreateDTO($session);

        // Vérifier les prérequis pour les étapes suivantes
        if (!$this->canAccessStep($step, $dto)) {
            $this->addFlash('warning', 
                $this->translationService->tp('flash.complete_previous_steps', [], 'credit_application_controller')
            );
            return $this->redirectToRoute('credit_application_step', ['step' => $this->getNextAccessibleStep($dto)]);
        }

        // Créer le formulaire correspondant à l'étape
        $form = $this->createStepForm($step, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour le DTO avec les données du formulaire
            $dto = $form->getData();
            
            // Calculer les données du crédit si nécessaire (étape 1 maintenant)
            if ($step === 1) {
                $dto->calculateMonthlyPayment();
                $dto->calculateTotalCost();
            }
            
            // Sauvegarder dans la session
            $session->set(self::SESSION_KEY, $dto);

            $this->logger->info('Étape de demande de crédit complétée', [
                'step' => $step,
                'completion' => $dto->getCompletionPercentage() . '%'
            ]);

            // Rediriger vers l'étape suivante ou finaliser
            if ($step < self::MAX_STEPS) {
                $this->addFlash('success', 
                    $this->translationService->tp('flash.step_completed', ['%d' => $step], 'credit_application_controller')
                );
                return $this->redirectToRoute('credit_application_step', ['step' => $step + 1]);
            } else {
                // Dernière étape : sauvegarder en base de données
                return $this->finalizeCreditApplication($dto, $request);
            }
        }

        return $this->render('credit_application/step.html.twig', [
            'form' => $form,
            'currentStep' => $step,
            'maxSteps' => self::MAX_STEPS,
            'dto' => $dto,
            'stepTitle' => $this->getStepTitle($step),
            'stepDescription' => $this->getStepDescription($step),
            'completionPercentage' => $dto->getCompletionPercentage(),
            'canGoBack' => $step > 1,
            'isLastStep' => $step === self::MAX_STEPS
        ]);
    }

    #[Route([
        'fr' => '/recapitulatif',
        'nl' => '/samenvatting',
        'en' => '/summary',
        'de' => '/zusammenfassung',
        'es' => '/resumen'
    ], name: 'credit_application_summary', requirements: ['_locale' => 'fr|nl|de|en|es'], methods: ['GET'])]
    public function summary(SessionInterface $session): Response
    {
        $dto = $session->get(self::SESSION_KEY);
        
        if (!$dto instanceof CreditApplicationDTO || !$dto->isStepComplete(3)) {
            $this->addFlash('danger', 
                $this->translationService->tp('flash.complete_all_steps', [], 'credit_application_controller')
            );
            return $this->redirectToRoute('credit_application_start');
        }

        return $this->render('credit_application/summary.html.twig', [
            'dto' => $dto,
            'csrfToken' => $this->csrfTokenManager->getToken('credit_application_submit')
        ]);
    }

    #[Route([
        'fr' => '/soumettre',
        'nl' => '/indienen',
        'en' => '/submit',
        'de' => '/einreichen',
        'es' => '/enviar'
    ], name: 'credit_application_submit', requirements: ['_locale' => 'fr|nl|de|en|es'], methods: ['POST'])]
    public function submit(Request $request, SessionInterface $session): Response
    {
        $dto = $session->get(self::SESSION_KEY);
        
        if (!$dto instanceof CreditApplicationDTO || !$dto->isStepComplete(3)) {
            $this->addFlash('danger', 
                $this->translationService->tp('flash.invalid_request', [], 'credit_application_controller')
            );
            return $this->redirectToRoute('credit_application_start');
        }

        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->csrfTokenManager->isTokenValid($this->csrfTokenManager->getToken('credit_application_submit'), $token)) {
            $this->addFlash('danger', 
                $this->translationService->tp('flash.invalid_security_token', [], 'credit_application_controller')
            );
            return $this->redirectToRoute('credit_application_summary');
        }

        return $this->finalizeCreditApplication($dto, $request);
    }

    #[Route('/api/calculate', name: 'credit_application_calculate', methods: ['POST'])]
    public function calculateLoan(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        
        $loanAmount = (float) ($data['loanAmount'] ?? 0);
        $duration = (int) ($data['duration'] ?? 0);
        $creditType = $data['creditType'] ?? null;

        if ($loanAmount <= 0 || $duration <= 0 || !$creditType) {
            return $this->json([
                'error' => $this->translationService->tp('api.invalid_data', [], 'credit_application_controller')
            ], 400);
        }

        try {
            $dto = new CreditApplicationDTO();
            $dto->loanAmount = $loanAmount;
            $dto->duration = $duration; // La durée est toujours en mois
            $dto->creditType = \App\Enum\CreditTypeEnum::from($creditType);

            $monthlyPayment = $dto->calculateMonthlyPayment();
            $totalCost = $dto->calculateTotalCost();

            return $this->json([
                'monthlyPayment' => $monthlyPayment,
                'totalCost' => $totalCost,
                'rate' => $dto->creditType->getRate(),
                'loanAmount' => $loanAmount
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du calcul du crédit', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            return $this->json(['error' => 'Erreur de calcul'], 500);
        }
    }

    private function getOrCreateDTO(SessionInterface $session): CreditApplicationDTO
    {
        $dto = $session->get(self::SESSION_KEY);
        
        if (!$dto instanceof CreditApplicationDTO) {
            $dto = new CreditApplicationDTO();
            $session->set(self::SESSION_KEY, $dto);
        }
        
        return $dto;
    }

    private function canAccessStep(int $step, CreditApplicationDTO $dto): bool
    {
        if ($step === 1) {
            return true;
        }
        
        if ($step === 2) {
            return $dto->isStepComplete(1);
        }
        
        if ($step === 3) {
            return $dto->isStepComplete(1) && $dto->isStepComplete(2);
        }
        
        return false;
    }

    private function getNextAccessibleStep(CreditApplicationDTO $dto): int
    {
        if (!$dto->isStepComplete(1)) {
            return 1;
        }
        
        if (!$dto->isStepComplete(2)) {
            return 2;
        }
        
        return 3;
    }

    private function createStepForm(int $step, CreditApplicationDTO $dto): \Symfony\Component\Form\FormInterface
    {
        return match($step) {
            1 => $this->createForm(CreditApplicationStep3Type::class, $dto), // Détails du crédit en premier
            2 => $this->createForm(CreditApplicationStep1Type::class, $dto), // Infos personnelles en deuxième
            3 => $this->createForm(CreditApplicationStep2Type::class, $dto), // Situation financière en troisième
            default => throw new \InvalidArgumentException(
                $this->translationService->tp('exceptions.invalid_step', [], 'credit_application_controller')
            )
        };
    }

    private function getStepTitle(int $step): string
    {
        return match($step) {
            1 => $this->translationService->tp('step1.title', [], 'credit_step_forms'),
            2 => $this->translationService->tp('step2.title', [], 'credit_step_forms'),
            3 => $this->translationService->tp('step3.title', [], 'credit_step_forms'),
            default => $this->translationService->tp('step1.title', [], 'credit_step_forms')
        };
    }

    private function getStepDescription(int $step): string
    {
        return match($step) {
            1 => $this->translationService->tp('step1.description', [], 'credit_step_forms'),
            2 => $this->translationService->tp('step2.description', [], 'credit_step_forms'),
            3 => $this->translationService->tp('step3.description', [], 'credit_step_forms'),
            default => $this->translationService->tp('step1.description', [], 'credit_step_forms')
        };
    }

    private function finalizeCreditApplication(CreditApplicationDTO $dto, Request $request): Response
    {
        try {
            // Assigner la locale de l'URL au DTO
            $dto->locale = $request->getLocale();
            
            $this->logger->info('🚀 Finalisation de la demande de crédit commencée', [
                'locale' => $dto->locale,
                'email' => $dto->email,
                'locale_request' => $request->getLocale()
            ]);
            
            // Créer l'entité CreditApplication depuis le DTO
            $creditApplication = $this->mapperService->mapDtoToEntity($dto);
            
            $this->logger->info('✅ Entité CreditApplication créée', [
                'id_temporaire' => 'avant_persist',
                'email' => $creditApplication->getEmail(),
                'locale' => $creditApplication->getLocale()
            ]);
            
            // Sauvegarder en base de données
            $this->entityManager->persist($creditApplication);
            $this->entityManager->flush();
            
            $this->logger->info('💾 Demande sauvegardée en BDD', [
                'id' => $creditApplication->getId(),
                'reference' => $creditApplication->getReferenceNumber(),
                'locale' => $creditApplication->getLocale()
            ]);

            // Dispatch le message pour l'envoi des emails avec la locale actuelle
            $locale = $request->getLocale();
            $this->messageBus->dispatch(new CreditApplicationSubmittedMessage($creditApplication->getId(), $locale));

            $this->logger->info('Demande de crédit créée', [
                'reference' => $creditApplication->getReferenceNumber(),
                'amount' => $creditApplication->getLoanAmount(),
                'email' => $creditApplication->getEmail()
            ]);

            // Nettoyer la session
            $request->getSession()->remove(self::SESSION_KEY);

            $this->addFlash('success', 
                $this->translationService->tp('flash.application_submitted', [], 'credit_application_controller')
            );
            
            return $this->redirectToRoute('credit_application_confirmation', [
                'reference' => $creditApplication->getReferenceNumber()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la finalisation de la demande de crédit', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addFlash('danger', 
                $this->translationService->tp('flash.submission_error', [], 'credit_application_controller')
            );
            return $this->redirectToRoute('credit_application_summary');
        }
    }

    #[Route([
        'fr' => '/confirmation/{reference}',
        'nl' => '/bevestiging/{reference}',
        'en' => '/confirmation/{reference}',
        'de' => '/bestaetigung/{reference}',
        'es' => '/confirmacion/{reference}'
    ], name: 'credit_application_confirmation', requirements: ['_locale' => 'fr|nl|de|en|es'], methods: ['GET'])]
    public function confirmation(string $reference): Response
    {
        $creditApplication = $this->entityManager->getRepository(CreditApplication::class)
            ->findOneBy(['referenceNumber' => $reference]);

        if (!$creditApplication) {
            throw $this->createNotFoundException(
                $this->translationService->tp('exceptions.credit_application_not_found', [], 'credit_application_controller')
            );
        }

        return $this->render('credit_application/confirmation.html.twig', [
            'creditApplication' => $creditApplication
        ]);
    }
}
