<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route([
    'fr' => '/{_locale}/reinitialiser-mot-de-passe',
    'nl' => '/{_locale}/wachtwoord-resetten', 
    'en' => '/{_locale}/reset-password',
    'de' => '/{_locale}/passwort-zurucksetzen',
    'es' => '/{_locale}/restablecer-contraseña'
], requirements: ['_locale' => 'fr|nl|de|en|es'])]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager,
        #[Autowire('%env(MAILER_FROM_EMAIL)%')] private string $fromEmail,
        #[Autowire('%env(MAILER_FROM_NAME)%')] private string $fromName
    ) {
    }

    /**
     * Display & process form to request a password reset.
     */
    #[Route([
        'fr' => '',
        'nl' => '',
        'en' => '',
        'de' => '',
        'es' => ''
    ], name: 'app_forgot_password_request', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function request(Request $request, MailerInterface $mailer, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $email */
            $email = $form->get('email')->getData();

            return $this->processSendingPasswordResetEmail($email, $mailer, $translator, $request
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form,
        ]);
    }

    /**
     * Confirmation page after a user has requested a password reset.
     */
    #[Route([
        'fr' => '/verifier-email',
        'nl' => '/controleer-email',
        'en' => '/check-email',
        'de' => '/email-prufen',
        'es' => '/verificar-email'
    ], name: 'app_check_email', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function checkEmail(): Response
    {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     */
    #[Route([
        'fr' => '/reinitialiser/{token}',
        'nl' => '/resetten/{token}',
        'en' => '/reset/{token}',
        'de' => '/zurucksetzen/{token}',
        'es' => '/restablecer/{token}'
    ], name: 'app_reset_password', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator, ?string $token = null): Response
    {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();

        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Encode(hash) the plain password, and set it.
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $this->entityManager->flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            $locale = $request->get('_locale', 'fr');
            return $this->redirectToRoute('home_index', ['_locale' => $locale]);
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form,
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer, TranslatorInterface $translator, Request $request): RedirectResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return $this->redirectToRoute('app_check_email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            // If you want to tell the user why a reset email was not sent, uncomment
            // the lines below and change the redirect to 'app_forgot_password_request'.
            // Caution: This may reveal if a user is registered or not.
            //
            // $this->addFlash('reset_password_error', sprintf(
            //     '%s - %s',
            //     $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_HANDLE, [], 'ResetPasswordBundle'),
            //     $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            // ));

            return $this->redirectToRoute('app_check_email');
        }

        // Get the current locale from the request
        $locale = $request->getLocale();

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to((string) $user->getEmail())
            ->subject($translator->trans('email.subject', [], 'reset_password'))
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
                'user' => $user,
                'locale' => $locale,
            ])
            ->locale($locale)
        ;

        $mailer->send($email);

        // Store the token object in session for retrieval in check-email route.
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_check_email');
    }

    // Routes de compatibilité sans locale (redirections)
    #[Route('/reset-password', name: 'app_forgot_password_request_legacy')]
    public function requestLegacy(Request $request): Response
    {
        $preferredLocale = $request->getPreferredLanguage(['fr', 'nl', 'de', 'en', 'es']) ?? 'fr';
        return $this->redirectToRoute('app_forgot_password_request', ['_locale' => $preferredLocale], 301);
    }

    #[Route('/reset-password/check-email', name: 'app_check_email_legacy')]
    public function checkEmailLegacy(Request $request): Response
    {
        $preferredLocale = $request->getPreferredLanguage(['fr', 'nl', 'de', 'en', 'es']) ?? 'fr';
        return $this->redirectToRoute('app_check_email', ['_locale' => $preferredLocale], 301);
    }

    #[Route('/reset-password/reset/{token}', name: 'app_reset_password_legacy')]
    public function resetLegacy(Request $request, string $token): Response
    {
        $preferredLocale = $request->getPreferredLanguage(['fr', 'nl', 'de', 'en', 'es']) ?? 'fr';
        return $this->redirectToRoute('app_reset_password', ['_locale' => $preferredLocale, 'token' => $token], 301);
    }
}
