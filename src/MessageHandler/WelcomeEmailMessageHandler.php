<?php

namespace App\MessageHandler;

use App\Message\WelcomeEmailMessage;
use App\Repository\UserRepository;
use App\Service\EmailService;
use App\Service\CompanySettingsService;
use App\Trait\CompanyPlaceholderReplacerTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Handler spécialisé pour les emails de bienvenue
 */
#[AsMessageHandler]
class WelcomeEmailMessageHandler
{
    use CompanyPlaceholderReplacerTrait;

    public function __construct(
        private EmailService $emailService,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private UserRepository $userRepository,
        private CompanySettingsService $companySettingsService,
        #[Autowire('%app.mailer.from_email%')] private string $defaultFromEmail,
        #[Autowire('%app.mailer.from_name%')] private string $defaultFromName
    ) {
    }

    public function __invoke(WelcomeEmailMessage $message): void
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
            
            $this->logger->info($this->translator->trans('email_welcome.logs.sending', [], 'email_welcome'), [
                'to' => $message->getTo(),
                'firstName' => $message->getContext()['firstName'] ?? 'N/A',
                'locale' => $locale
            ]);

            // Traduire le sujet si c'est une clé de traduction
            $subject = $message->getSubject();
            if ($message->getTranslationDomain() && strpos($subject, '.') !== false) {
                $subject = $this->translator->trans($subject, [], $message->getTranslationDomain(), $locale);
                $subject = $this->replaceCompanyPlaceholders($subject);
            }

            $success = $this->emailService->sendEmailAsync(
                $message->getTo(),
                $subject,
                $message->getTemplate(),
                $message->getContext(),
                $message->getToName(),
                $message->getFromEmail() ?? $this->defaultFromEmail,
                $message->getFromName() ?? $this->defaultFromName,
                $locale // Nouvelle locale passée au service
            );

            if (!$success) {
                throw new \RuntimeException($this->translator->trans('email_welcome.logs.send_failed', [], 'email_welcome'));
            }

            $this->logger->info($this->translator->trans('email_welcome.logs.success', [], 'email_welcome'), [
                'to' => $message->getTo()
            ]);

        } catch (\Exception $e) {
            $this->logger->error($this->translator->trans('email_welcome.logs.error', [], 'email_welcome'), [
                'to' => $message->getTo(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
