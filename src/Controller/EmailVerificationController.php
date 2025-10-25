<?php

namespace App\Controller;

use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route([
    'fr' => '/{_locale}/verification-email',
    'nl' => '/{_locale}/email-verificatie',
    'en' => '/{_locale}/email-verification',
    'de' => '/{_locale}/email-verifizierung',
    'es' => '/{_locale}/verificacion-email'
], requirements: ['_locale' => 'fr|nl|de|en|es'])]
#[IsGranted('ROLE_USER')]
class EmailVerificationController extends AbstractController
{
    public function __construct(
        private EmailVerificationService $emailVerificationService,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * Page de validation d'un changement d'email
     */
    #[Route([
        'fr' => '/verifier/{token}',
        'nl' => '/verifieer/{token}',
        'en' => '/verify/{token}',
        'de' => '/verifizieren/{token}',
        'es' => '/verificar/{token}'
    ], name: 'app_email_verification', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function verify(string $token): Response
    {
        try {
            $user = $this->emailVerificationService->confirmEmailChange($token);

            $this->addFlash('success', $this->translator->trans(
                'email_verification.success',
                ['email' => $user->getEmail()],
                'messages'
            ));

            return $this->redirectToRoute('profile_index');

        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $this->translator->trans(
                'email_verification.error',
                ['message' => $e->getMessage()],
                'messages'
            ));

            return $this->redirectToRoute('profile_index');
        }
    }

    /**
     * Annulation d'un changement d'email en cours
     */
    #[Route([
        'fr' => '/annuler',
        'nl' => '/annuleren',
        'en' => '/cancel',
        'de' => '/abbrechen',
        'es' => '/cancelar'
    ], name: 'app_email_verification_cancel', requirements: ['_locale' => 'fr|nl|de|en|es'], methods: ['POST'])]
    public function cancel(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$user->getPendingEmail()) {
            $this->addFlash('info', $this->translator->trans(
                'email_verification.no_pending_change',
                [],
                'messages'
            ));

            return $this->redirectToRoute('profile_index');
        }

        if ($this->isCsrfTokenValid('cancel_email_change', $request->get('_token'))) {
            $this->emailVerificationService->cancelEmailChange($user);

            $this->addFlash('success', $this->translator->trans(
                'email_verification.cancelled',
                [],
                'messages'
            ));
        } else {
            $this->addFlash('danger', $this->translator->trans(
                'security.csrf_invalid',
                [],
                'messages'
            ));
        }

        return $this->redirectToRoute('profile_index');
    }
}
