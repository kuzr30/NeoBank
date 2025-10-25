<?php

namespace App\MessageHandler;

use App\Message\BankingNotificationEmailMessage;
use App\Repository\UserRepository;
use App\Service\EmailService;
use App\Service\CompanySettingsService;
use App\Trait\CompanyPlaceholderReplacerTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Handler spécialisé pour les notifications bancaires
 */
#[AsMessageHandler]
class BankingNotificationEmailMessageHandler
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

    public function __invoke(BankingNotificationEmailMessage $message): void
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
            
            $this->logger->info($this->translator->trans('email_banking_notification.logs.sending', [], 'email_banking_notification'), [
                'to' => $message->getTo(),
                'type' => $context['notificationType'] ?? 'unknown',
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
                throw new \RuntimeException($this->translator->trans('email_banking_notification.logs.send_failed', [], 'email_banking_notification'));
            }

            $this->logger->info($this->translator->trans('email_banking_notification.logs.success', [], 'email_banking_notification'), [
                'to' => $message->getTo(),
                'type' => $context['notificationType'] ?? 'unknown'
            ]);

        } catch (\Exception $e) {
            $this->logger->error($this->translator->trans('email_banking_notification.logs.error', [], 'email_banking_notification'), [
                'to' => $message->getTo(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
