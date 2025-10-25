<?php

namespace App\MessageHandler;

use App\Message\AccountCreatedNotificationMessage;
use App\Service\EmailService;
use App\Service\ProfessionalTranslationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

/**
 * Handler pour les notifications de création de compte
 */
#[AsMessageHandler]
class AccountCreatedNotificationMessageHandler
{
    public function __construct(
        private EmailService $emailService,
        private LoggerInterface $logger,
        private ProfessionalTranslationService $translationService,
        private string $mailerFromEmail,
        private string $mailerFromName
    ) {
    }

    public function __invoke(AccountCreatedNotificationMessage $message): void
    {
        try {
            $context = $message->getContext();
            $locale = $context['locale'] ?? 'fr';
            
            // Utiliser la locale de soumission
            $originalLocale = $this->translationService->getLocale();
            $this->translationService->setLocale($locale);

            $this->logger->info('Envoi de la notification de création de compte', [
                'to' => $message->getTo(),
                'firstName' => $context['firstName'] ?? 'N/A',
                'accountNumber' => $context['accountNumber'] ?? 'N/A',
                'locale' => $locale
            ]);

            // Le sujet sera traduit automatiquement avec la locale définie
            $subject = $this->translationService->tp($message->getSubject(), [], $message->getTranslationDomain());

            // Ajouter le service de traduction au contexte pour les templates
            $context = $message->getContext();
            $context['translationService'] = $this->translationService;

            $success = $this->emailService->sendEmailAsync(
                $message->getTo(),
                $subject,
                $message->getTemplate(),
                $context,
                $message->getToName(),
                $message->getFromEmail() ?? $this->mailerFromEmail,
                $message->getFromName() ?? $this->mailerFromName,
                $locale // Passer la locale au service
            );

            if (!$success) {
                throw new \RuntimeException('Échec de l\'envoi de l\'email de création de compte');
            }

            $this->logger->info('Notification de création de compte envoyée avec succès', [
                'to' => $message->getTo(),
                'accountNumber' => $context['accountNumber'] ?? 'N/A',
                'locale' => $locale
            ]);

            // Restaurer la locale originale
            $this->translationService->setLocale($originalLocale);

        } catch (\Exception $e) {
            // Restaurer la locale en cas d'erreur
            if (isset($originalLocale)) {
                $this->translationService->setLocale($originalLocale);
            }
            
            $this->logger->error('Erreur lors de l\'envoi de la notification de création de compte', [
                'to' => $message->getTo(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}
