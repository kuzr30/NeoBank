<?php

namespace App\MessageHandler;

use App\Message\RibAddedNotificationMessage;
use App\Repository\UserRepository;
use App\Service\EmailService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Handler pour les notifications d'ajout de RIB
 */
#[AsMessageHandler]
class RibAddedNotificationMessageHandler
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

    public function __invoke(RibAddedNotificationMessage $message): void
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
            
            $this->logger->info($this->translator->trans('email_rib_added_notification.logs.sending', [], 'email_rib_added_notification'), [
                'to' => $message->getTo(),
                'accountName' => $message->getAccountName(),
                'bankName' => $message->getBankName(),
                'locale' => $locale
            ]);

            // Traduire le sujet de l'email
            $translatedSubject = $this->translator->trans(
                $message->getSubject(), 
                [], 
                $message->getTranslationDomain(),
                $locale
            );

            $success = $this->emailService->sendEmailAsync(
                $message->getTo(),
                $translatedSubject,
                $message->getTemplate(),
                $message->getContext(),
                $message->getToName(),
                $message->getFromEmail() ?? $this->defaultFromEmail,
                $message->getFromName() ?? $this->defaultFromName,
                $locale // Nouvelle locale passée au service
            );

            if (!$success) {
                throw new \RuntimeException($this->translator->trans('email_rib_added_notification.logs.send_failed', [], 'email_rib_added_notification'));
            }

            $this->logger->info($this->translator->trans('email_rib_added_notification.logs.success', [], 'email_rib_added_notification'), [
                'to' => $message->getTo(),
                'accountName' => $message->getAccountName()
            ]);

        } catch (\Exception $e) {
            $this->logger->error($this->translator->trans('email_rib_added_notification.logs.error', [], 'email_rib_added_notification'), [
                'to' => $message->getTo(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}
