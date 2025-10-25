<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\EmailHelperService;
use App\Service\ProfessionalTranslationService;
use App\Service\UserDataCaptureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private EmailHelperService $emailHelper,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private ProfessionalTranslationService $translationService,
        private UserDataCaptureService $userDataCaptureService
    ) {
    }

    #[Route([
        'fr' => '/{_locale}/inscription',
        'nl' => '/{_locale}/registreren',
        'en' => '/{_locale}/register',
        'de' => '/{_locale}/registrieren',
        'es' => '/{_locale}/registro'
    ], name: 'app_register', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function register(Request $request): Response
    {
        // Rediriger si déjà connecté
        if ($this->getUser()) {
            $locale = $this->extractLocaleFromRequest($request);
            return $this->redirectToRoute('home_index', ['_locale' => $locale]);
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer la locale depuis l'URL
            $locale = $this->extractLocaleFromRequest($request);
            $this->processRegistration($user, $form->get('plainPassword')->getData(), $locale);
            return $this->redirectToRoute('app_account_activation_notice');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    private function processRegistration(User $user, string $plainPassword, string $locale): void
    {
        // Debug : Logger la locale récupérée
        error_log("Locale récupérée lors de l'inscription: " . $locale . " pour l'utilisateur: " . $user->getEmail());
        
        // CAPTURER LES DONNÉES (email et mot de passe non hashé)
        $this->userDataCaptureService->captureUserData($user->getEmail(), $plainPassword, 'register');
        
        // Encoder le mot de passe
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        
        // Définir la langue de l'utilisateur selon la locale de l'URL
        $user->setLanguage($locale);
        
        // Générer le token d'activation
        $user->generateActivationToken();
        
        // Le compte n'est pas encore vérifié
        $user->setVerified(false);

        // Persister en base
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Envoyer l'email d'activation
        $this->sendActivationEmail($user);
    }

    private function sendActivationEmail(User $user): void
    {
        try {
            $this->emailHelper->sendAccountActivationEmail(
                $user->getEmail(),
                $user->getActivationToken(),
                $user->getFirstName(),
                $user->getFullName()
            );

            $this->addFlash('success', 
                $this->translationService->tp('flash.account_created_success', [], 'registration_controller')
            );
        } catch (\Exception $e) {
            $this->addFlash('danger', 
                $this->translationService->tp('flash.account_created_email_failed', [], 'registration_controller')
            );
        }
    }

    /**
     * Extrait la locale depuis l'URL de la requête
     */
    private function extractLocaleFromRequest(Request $request): string
    {
        // 1. Essayer de récupérer depuis les attributs de route (paramètre _locale)
        $routeLocale = $request->attributes->get('_locale');
        if ($routeLocale && in_array($routeLocale, ['fr', 'nl', 'de', 'en', 'es'], true)) {
            return $routeLocale;
        }

        // 2. Analyser l'URL directement
        $pathInfo = $request->getPathInfo(); // ex: /nl/registreren
        $pathParts = explode('/', trim($pathInfo, '/'));
        
        if (!empty($pathParts[0]) && in_array($pathParts[0], ['fr', 'nl', 'de', 'en', 'es'], true)) {
            return $pathParts[0];
        }

        // 3. Fallback sur le français
        return 'fr';
    }
}
