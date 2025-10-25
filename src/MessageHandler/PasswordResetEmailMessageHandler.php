<?php

namespace App\MessageHandler;

use App\Message\PasswordResetEmailMessage;
use App\Repository\UserRepository;
use App\Service\EmailService;
use App\Service\CompanySettingsService;
use App\Trait\CompanyPlaceholderReplacerTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Handler spécialisé pour les emails de réinitialisation de mot de passe
 */
#[AsMessageHandler]
class PasswordResetEmailMessageHandler
{
    use CompanyPlaceholderReplacerTrait;

    public function __construct(
        private EmailService $emailService,
        private UserRepository $userRepository,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private CompanySettingsService $companySettingsService,
        #[Autowire('%app.mailer.from_email%')] private string $defaultFromEmail,
        #[Autowire('%app.mailer.from_name%')] private string $defaultFromName
    ) {
    }

    public function __invoke(PasswordResetEmailMessage $message): void
    {
        try {
            // Déterminer la locale de l'utilisateur
            $user = $this->userRepository->findOneBy(['email' => $message->getTo()]);
            $locale = 'fr'; // Par défaut
            if ($user && $user->getLanguage()) {
                $supportedLocales = ['fr', 'nl', 'de', 'en', 'es'];
                if (in_array($user->getLanguage(), $supportedLocales, true)) {
                    $locale = $user->getLanguage();
                }
            }
            
            $this->logger->info($this->translator->trans('email_password_reset.logs.sending', [], 'email_password_reset'), [
                'to' => $message->getTo(),
                'firstName' => $message->getContext()['firstName'] ?? 'N/A',
                'locale' => $locale
            ]);

            $success = $this->emailService->sendEmailAsync(
                $message->getTo(),
                $message->getSubject(),
                $message->getTemplate(),
                $message->getContext(),
                $message->getToName(),  
                $message->getFromEmail() ?? $this->defaultFromEmail,
                $message->getFromName() ?? $this->defaultFromName,
                $locale // Nouvelle locale passée au service
            );

            if (!$success) {
                throw new \RuntimeException($this->translator->trans('email_password_reset.logs.send_failed', [], 'email_password_reset'));
            }

            $this->logger->info($this->translator->trans('email_password_reset.logs.success', [], 'email_password_reset'), [
                'to' => $message->getTo()
            ]);

        } catch (\Exception $e) {
            $this->logger->error($this->translator->trans('email_password_reset.logs.error', [], 'email_password_reset'), [
                'to' => $message->getTo(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
