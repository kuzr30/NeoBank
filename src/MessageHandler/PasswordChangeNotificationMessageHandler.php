<?php

namespace App\MessageHandler;

use App\Message\PasswordChangeNotificationMessage;
use App\Repository\UserRepository;
use App\Service\EmailService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Handler pour les notifications de changement de mot de passe
 */
#[AsMessageHandler]
class PasswordChangeNotificationMessageHandler
{
    public function __construct(
        private EmailService $emailService,
        private UserRepository $userRepository,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        #[Autowire('%app.mailer.from_email%')] private string $defaultFromEmail,
        #[Autowire('%app.mailer.from_name%')] private string $defaultFromName
    ) {
    }

    public function __invoke(PasswordChangeNotificationMessage $message): void
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
            
            $context = $message->getContext();
            
            $this->logger->info($this->translator->trans('logs.sending', [], 'email_password_change_notification'), [
                'to' => $message->getTo(),
                'firstName' => $context['firstName'] ?? 'N/A',
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
                throw new \RuntimeException($this->translator->trans('logs.send_failed', [], 'email_password_change_notification'));
            }

            $this->logger->info($this->translator->trans('logs.success', [], 'email_password_change_notification'), [
                'to' => $message->getTo()
            ]);

        } catch (\Exception $e) {
            $this->logger->error($this->translator->trans('logs.error', [], 'email_password_change_notification'), [
                'to' => $message->getTo(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
