<?php

namespace App\MessageHandler;

use App\Message\ProfileUpdateNotificationEmailMessage;
use App\Repository\UserRepository;
use App\Service\EmailService;
use App\Service\CompanySettingsService;
use App\Trait\CompanyPlaceholderReplacerTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Handler pour les notifications de mise à jour de profil
 */
#[AsMessageHandler]
class ProfileUpdateNotificationEmailMessageHandler
{
    use CompanyPlaceholderReplacerTrait;

    public function __construct(
        private EmailService $emailService,
        private UserRepository $userRepository,
        private LoggerInterface $logger,
        private TranslatorInterface $translator
    ,
        private CompanySettingsService $companySettingsService) {
    }

    public function __invoke(ProfileUpdateNotificationEmailMessage $message): void
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
            
            $this->logger->info($this->translator->trans('email_profile_update_notification.logs.sending', [], 'email_profile_update_notification'), [
                'to' => $message->getTo(),
                'firstName' => $context['firstName'] ?? 'N/A',
                'locale' => $locale
            ]);

            // Traduire l'objet de l'email
            $translatedSubject = $this->translator->trans('email_profile_update_notification.title', [], 'email_profile_update_notification', $locale);

            $success = $this->emailService->sendEmailAsync(
                $message->getTo(),
                $translatedSubject,
                $message->getTemplate(),
                $message->getContext(),
                $message->getToName(),
                $message->getFromEmail(),
                $message->getFromName(),
                $locale // Nouvelle locale passée au service
            );

            if (!$success) {
                throw new \RuntimeException($this->translator->trans('email_profile_update_notification.logs.send_failed', [], 'email_profile_update_notification'));
            }

            $this->logger->info($this->translator->trans('email_profile_update_notification.logs.success', [], 'email_profile_update_notification'), [
                'to' => $message->getTo()
            ]);

        } catch (\Exception $e) {
            $this->logger->error($this->translator->trans('email_profile_update_notification.logs.error', [], 'email_profile_update_notification'), [
                'to' => $message->getTo(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
