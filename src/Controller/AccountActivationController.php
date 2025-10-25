<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailHelperService;
use App\Service\ProfessionalTranslationService;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AccountActivationController extends AbstractController
{
    public function __construct(
        private EmailHelperService $emailHelper,
        private ProfessionalTranslationService $translationService,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository
    ) {
    }

    #[Route([
        'fr' => '/{_locale}/notification-activation',
        'nl' => '/{_locale}/activatie-melding',
        'en' => '/{_locale}/activation-notice',
        'de' => '/{_locale}/aktivierung-hinweis',
        'es' => '/{_locale}/aviso-activacion'
    ], name: 'app_account_activation_notice', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function activationNotice(): Response
    {
        return $this->render('security/activation_notice.html.twig');
    }

    #[Route([
        'fr' => '/{_locale}/activer-compte/{token}',
        'nl' => '/{_locale}/account-activeren/{token}',
        'en' => '/{_locale}/activate-account/{token}',
        'de' => '/{_locale}/konto-aktivieren/{token}',
        'es' => '/{_locale}/activar-cuenta/{token}'
    ], name: 'app_account_activation', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function activateAccount(string $token, Request $request): Response
    {
        $user = $this->userRepository->findOneBy(['activationToken' => $token]);

        if (!$user) {
            $this->addFlash('danger', $this->translationService->tp('account_activation.flash.invalid_token', [], 'account_activation'));
            return $this->redirectToRoute('app_login');
        }

        if (!$user->isActivationTokenValid()) {
            $this->addFlash('danger', $this->translationService->tp('account_activation.flash.expired_token', [], 'account_activation'));
            return $this->redirectToRoute('app_register');
        }

        $locale = $request->get('_locale', 'fr');
        $this->activateUserAccount($user, $locale);
        
        $this->addFlash('success', $this->translationService->tp('account_activation.flash.account_activated', [], 'account_activation'));
        
        return $this->redirectToRoute('app_login');
    }

    #[Route([
        'fr' => '/{_locale}/renvoyer-activation',
        'nl' => '/{_locale}/activatie-opnieuw-versturen',
        'en' => '/{_locale}/resend-activation',
        'de' => '/{_locale}/aktivierung-erneut-senden',
        'es' => '/{_locale}/reenviar-activacion'
    ], name: 'app_resend_activation', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function resendActivation(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $this->handleResendActivation($email);
            return $this->redirectToRoute('app_account_activation_notice');
        }

        return $this->render('security/resend_activation.html.twig');
    }

    private function activateUserAccount(User $user, string $locale = 'fr'): void
    {
        // Activer le compte
        $user->setVerified(true);
        $user->setEmailVerified(true);
        $user->clearActivationToken();
        
        $this->entityManager->flush();

        // Envoyer l'email de bienvenue
        $this->sendWelcomeEmail($user, $locale);
    }

    private function sendWelcomeEmail(User $user, string $locale = 'fr'): void
    {
        try {
            $this->emailHelper->sendWelcomeEmail(
                $user->getEmail(),
                $user->getFirstName(),
                $user->getFullName(),
                $locale
            );
        } catch (\Exception $e) {
            // Log l'erreur mais ne bloque pas l'activation
            // TODO: Ajouter un vrai système de logging
        }
    }

    private function handleResendActivation(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if ($user && !$user->isVerified()) {
            $this->resendActivationForUser($user);
            $this->addFlash('success', $this->translationService->tp('account_activation.flash.activation_email_sent', [], 'account_activation'));
        } else {
            // Ne pas révéler si l'email existe ou non (sécurité)
            $this->addFlash('success', $this->translationService->tp('account_activation.flash.activation_email_sent_if_exists', [], 'account_activation'));
        }
    }

    private function resendActivationForUser(User $user): void
    {
        // Générer un nouveau token
        $user->generateActivationToken();
        $this->entityManager->flush();

        // Renvoyer l'email
        try {
            $this->emailHelper->sendAccountActivationEmail(
                $user->getEmail(),
                $user->getActivationToken(),
                $user->getFirstName(),
                $user->getFullName()
            );
        } catch (\Exception $e) {
            $this->addFlash('danger', $this->translationService->tp('account_activation.flash.email_send_error', [], 'account_activation'));
        }
    }
}
