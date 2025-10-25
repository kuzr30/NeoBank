<?php

namespace App\Service;

use App\Entity\Transfer;
use App\Entity\TransferCode;
use App\Service\ProfessionalTranslationService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Psr\Log\LoggerInterface;

class TransferNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private ProfessionalTranslationService $translationService,
        #[Autowire('%app.mailer.from_email%')] private string $fromEmail,
        #[Autowire('%app.mailer.from_name%')] private string $fromName,
        #[Autowire('%app.admin_email%')] private string $adminEmail
    ) {
    }

    /**
     * Notification lors de la création d'un virement
     */
    public function notifyTransferCreated(Transfer $transfer): void
    {
        try {
            // Gérer la locale de l'utilisateur
            $user = $transfer->getUser();
            $userLocale = $user->getLanguage() ?? 'fr';
            $supportedLocales = ['fr', 'nl', 'de', 'en', 'es'];
            if (!in_array($userLocale, $supportedLocales, true)) {
                $userLocale = 'fr';
            }
            
            // Sauvegarder la locale actuelle
            $originalLocale = $this->translationService->getLocale();
            $this->translationService->setLocale($userLocale);
            
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($transfer->getUser()->getEmail())
                ->subject($this->translationService->tp('email_transfer_created.title', [], 'email_transfer_created'))
                ->htmlTemplate('email/transfer/created.html.twig')
                ->context([
                    'transfer' => $transfer,
                    'user' => $transfer->getUser(),
                ]);

            $this->mailer->send($email);
            
            $this->logger->info($this->translationService->tp('log_messages.transfer_creation_sent', [], 'transfer_notification_service'), [
                'transfer_id' => $transfer->getId(),
                'user_email' => $transfer->getUser()->getEmail()
            ]);
            
            // Restaurer la locale originale
            $this->translationService->setLocale($originalLocale);
        } catch (\Exception $e) {
            // Restaurer la locale en cas d'erreur
            if (isset($originalLocale)) {
                $this->translationService->setLocale($originalLocale);
            }
            
            $this->logger->error($this->translationService->tp('log_messages.transfer_creation_failed', [], 'transfer_notification_service'), [
                'transfer_id' => $transfer->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notification lors de l'ajout d'un nouveau code
     */
    public function notifyCodeAdded(Transfer $transfer, TransferCode $code): void
    {
        try {
            // Gérer la locale de l'utilisateur
            $user = $transfer->getUser();
            $userLocale = $user->getLanguage() ?? 'fr';
            $supportedLocales = ['fr', 'nl', 'de', 'en', 'es'];
            if (!in_array($userLocale, $supportedLocales, true)) {
                $userLocale = 'fr';
            }
            
            // Sauvegarder la locale actuelle
            $originalLocale = $this->translationService->getLocale();
            $this->translationService->setLocale($userLocale);
            
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($transfer->getUser()->getEmail())
                ->subject($this->translationService->tp('email_transfer_attention.title', [], 'email_transfer_attention'))
                ->htmlTemplate('email/transfer/code_added.html.twig')
                ->context([
                    'transfer' => $transfer,
                    'code' => $code,
                    'user' => $transfer->getUser(),
                ]);

            $this->mailer->send($email);
            
            $this->logger->info($this->translationService->tp('log_messages.code_added_sent', [], 'transfer_notification_service'), [
                'transfer_id' => $transfer->getId(),
                'code_id' => $code->getId(),
                'user_email' => $transfer->getUser()->getEmail()
            ]);
            
            // Restaurer la locale originale
            $this->translationService->setLocale($originalLocale);
        } catch (\Exception $e) {
            // Restaurer la locale en cas d'erreur
            if (isset($originalLocale)) {
                $this->translationService->setLocale($originalLocale);
            }
            
            $this->logger->error($this->translationService->tp('log_messages.code_added_failed', [], 'transfer_notification_service'), [
                'transfer_id' => $transfer->getId(),
                'code_id' => $code->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notification lors de la validation d'un code
     */
    public function notifyCodeValidated(Transfer $transfer, TransferCode $code): void
    {
        try {
            // Gérer la locale de l'utilisateur
            $user = $transfer->getUser();
            $userLocale = $user->getLanguage() ?? 'fr';
            
            // Vérifier si la locale est supportée
            $supportedLocales = ['fr', 'nl', 'de', 'en', 'es'];
            if (!in_array($userLocale, $supportedLocales, true)) {
                $userLocale = 'fr';
            }
            
            // Sauvegarder la locale actuelle
            $originalLocale = $this->translationService->getLocale();
            $this->translationService->setLocale($userLocale);
            
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($transfer->getUser()->getEmail())
                ->subject($this->translationService->tp('email_subjects.code_validated', [], 'transfer_notification_service'))
                ->htmlTemplate('email/transfer/code_validated.html.twig')
                ->context([
                    'transfer' => $transfer,
                    'code' => $code,
                    'user' => $transfer->getUser(),
                    'remaining_codes' => $transfer->getTransferCodes()->filter(fn($c) => !$c->isValidated())->count()
                ]);

            $this->mailer->send($email);
            
            // Restaurer la locale originale
            $this->translationService->setLocale($originalLocale);
            
            $this->logger->info($this->translationService->tp('log_messages.code_validation_sent', [], 'transfer_notification_service'), [
                'transfer_id' => $transfer->getId(),
                'code_id' => $code->getId(),
                'user_email' => $transfer->getUser()->getEmail()
            ]);
        } catch (\Exception $e) {
            // Restaurer la locale originale en cas d'erreur
            if (isset($originalLocale)) {
                $this->translationService->setLocale($originalLocale);
            }
            
            $this->logger->error($this->translationService->tp('log_messages.code_validation_failed', [], 'transfer_notification_service'), [
                'transfer_id' => $transfer->getId(),
                'code_id' => $code->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notification lorsque toutes les validations sont terminées
     */
    public function notifyAllCodesValidated(Transfer $transfer): void
    {
        try {
            // Gérer la locale de l'utilisateur
            $user = $transfer->getUser();
            $userLocale = $user->getLanguage() ?? 'fr';
            
            // Vérifier si la locale est supportée
            $supportedLocales = ['fr', 'nl', 'de', 'en', 'es'];
            if (!in_array($userLocale, $supportedLocales, true)) {
                $userLocale = 'fr';
            }
            
            // Sauvegarder la locale actuelle
            $originalLocale = $this->translationService->getLocale();
            $this->translationService->setLocale($userLocale);
            
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($transfer->getUser()->getEmail())
                ->subject($this->translationService->tp('email_subjects.ready_for_execution', [], 'transfer_notification_service'))
                ->htmlTemplate('email/transfer/ready_for_execution.html.twig')
                ->context([
                    'transfer' => $transfer,
                    'user' => $transfer->getUser(),
                ]);

            $this->mailer->send($email);
            
            // Restaurer la locale originale
            $this->translationService->setLocale($originalLocale);
            
            $this->logger->info($this->translationService->tp('log_messages.all_codes_validated_sent', [], 'transfer_notification_service'), [
                'transfer_id' => $transfer->getId(),
                'user_email' => $transfer->getUser()->getEmail()
            ]);
        } catch (\Exception $e) {
            // Restaurer la locale originale en cas d'erreur
            if (isset($originalLocale)) {
                $this->translationService->setLocale($originalLocale);
            }
            
            $this->logger->error($this->translationService->tp('log_messages.all_codes_validated_failed', [], 'transfer_notification_service'), [
                'transfer_id' => $transfer->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notification lors de l'exécution du virement
     */
    public function notifyTransferExecuted(Transfer $transfer): void
    {
        try {
            // Gérer la locale de l'utilisateur
            $user = $transfer->getUser();
            $userLocale = $user->getLanguage() ?? 'fr';
            
            // Vérifier si la locale est supportée
            $supportedLocales = ['fr', 'nl', 'de', 'en', 'es'];
            if (!in_array($userLocale, $supportedLocales, true)) {
                $userLocale = 'fr';
            }
            
            // Sauvegarder la locale actuelle
            $originalLocale = $this->translationService->getLocale();
            $this->translationService->setLocale($userLocale);
            
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($transfer->getUser()->getEmail())
                ->subject($this->translationService->tp('email_transfer_executed.title', [], 'email_transfer_executed'))
                ->htmlTemplate('email/transfer/executed.html.twig')
                ->context([
                    'transfer' => $transfer,
                    'user' => $transfer->getUser(),
                ]);

            $this->mailer->send($email);
            
            // Restaurer la locale originale
            $this->translationService->setLocale($originalLocale);
            
            $this->logger->info($this->translationService->tp('log_messages.transfer_executed_sent', [], 'transfer_notification_service'), [
                'transfer_id' => $transfer->getId(),
                'user_email' => $transfer->getUser()->getEmail()
            ]);
        } catch (\Exception $e) {
            // Restaurer la locale originale en cas d'erreur
            if (isset($originalLocale)) {
                $this->translationService->setLocale($originalLocale);
            }
            
            $this->logger->error($this->translationService->tp('log_messages.transfer_executed_failed', [], 'transfer_notification_service'), [
                'transfer_id' => $transfer->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notification lors du blocage d'un utilisateur
     */
    public function notifyUserBlocked(Transfer $transfer): void
    {
        try {
            // Gérer la locale de l'utilisateur
            $user = $transfer->getUser();
            $userLocale = $user->getLanguage() ?? 'fr';
            
            // Vérifier si la locale est supportée
            $supportedLocales = ['fr', 'nl', 'de', 'en', 'es'];
            if (!in_array($userLocale, $supportedLocales, true)) {
                $userLocale = 'fr';
            }
            
            // Sauvegarder la locale actuelle
            $originalLocale = $this->translationService->getLocale();
            $this->translationService->setLocale($userLocale);
            
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($transfer->getUser()->getEmail())
                ->subject($this->translationService->tp('email_user_blocked.title', [], 'email_user_blocked'))
                ->htmlTemplate('email/transfer/user_blocked.html.twig')
                ->context([
                    'transfer' => $transfer,
                    'user' => $transfer->getUser(),
                ]);

            $this->mailer->send($email);
            
            // Restaurer la locale originale
            $this->translationService->setLocale($originalLocale);
            
            $this->logger->info('User blocked notification sent', [
                'transfer_id' => $transfer->getId(),
                'user_email' => $transfer->getUser()->getEmail()
            ]);
        } catch (\Exception $e) {
            // Restaurer la locale originale en cas d'erreur
            if (isset($originalLocale)) {
                $this->translationService->setLocale($originalLocale);
            }
            
            $this->logger->error('Failed to send user blocked notification', [
                'transfer_id' => $transfer->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notification lors de l'expiration d'un code
     */
    public function notifyCodeExpired(Transfer $transfer, TransferCode $code): void
    {
        try {
            // Gérer la locale de l'utilisateur
            $user = $transfer->getUser();
            $userLocale = $user->getLanguage() ?? 'fr';
            
            // Vérifier si la locale est supportée
            $supportedLocales = ['fr', 'nl', 'de', 'en', 'es'];
            if (!in_array($userLocale, $supportedLocales, true)) {
                $userLocale = 'fr';
            }
            
            // Sauvegarder la locale actuelle
            $originalLocale = $this->translationService->getLocale();
            $this->translationService->setLocale($userLocale);
            
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($transfer->getUser()->getEmail())
                ->subject($this->translationService->tp('email_subjects.code_expired', [], 'transfer_notification_service'))
                ->htmlTemplate('email/transfer/code_expired.html.twig')
                ->context([
                    'transfer' => $transfer,
                    'code' => $code,
                    'user' => $transfer->getUser(),
                ]);

            $this->mailer->send($email);
            
            // Restaurer la locale originale
            $this->translationService->setLocale($originalLocale);
            
            $this->logger->info('Code expiration notification sent', [
                'transfer_id' => $transfer->getId(),
                'code_id' => $code->getId(),
                'user_email' => $transfer->getUser()->getEmail()
            ]);
        } catch (\Exception $e) {
            // Restaurer la locale originale en cas d'erreur
            if (isset($originalLocale)) {
                $this->translationService->setLocale($originalLocale);
            }
            
            $this->logger->error('Failed to send code expiration notification', [
                'transfer_id' => $transfer->getId(),
                'code_id' => $code->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notification lors de l'annulation d'un virement
     */
    public function notifyTransferCancelled(Transfer $transfer): void
    {
        try {
            // Gérer la locale de l'utilisateur
            $user = $transfer->getUser();
            $userLocale = $user->getLanguage() ?? 'fr';
            
            // Vérifier si la locale est supportée
            $supportedLocales = ['fr', 'nl', 'de', 'en', 'es'];
            if (!in_array($userLocale, $supportedLocales, true)) {
                $userLocale = 'fr';
            }
            
            // Sauvegarder la locale actuelle
            $originalLocale = $this->translationService->getLocale();
            $this->translationService->setLocale($userLocale);
            
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($transfer->getUser()->getEmail())
                ->subject($this->translationService->tp('email_subjects.transfer_cancelled', [], 'transfer_notification_service'))
                ->htmlTemplate('email/transfer/cancelled.html.twig')
                ->context([
                    'transfer' => $transfer,
                    'user' => $transfer->getUser(),
                ]);

            $this->mailer->send($email);
            
            // Restaurer la locale originale
            $this->translationService->setLocale($originalLocale);
            
            $this->logger->info('Transfer cancellation notification sent', [
                'transfer_id' => $transfer->getId(),
                'user_email' => $transfer->getUser()->getEmail()
            ]);
        } catch (\Exception $e) {
            // Restaurer la locale originale en cas d'erreur
            if (isset($originalLocale)) {
                $this->translationService->setLocale($originalLocale);
            }
            
            $this->logger->error('Failed to send transfer cancellation notification', [
                'transfer_id' => $transfer->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notification d'alerte pour l'administration
     */
    public function notifyAdminSuspiciousActivity(Transfer $transfer, string $reason): void
    {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($this->adminEmail)
                ->subject('Activité suspecte détectée - Virement #' . $transfer->getId())
                ->htmlTemplate('email/admin/suspicious_activity.html.twig')
                ->context([
                    'transfer' => $transfer,
                    'reason' => $reason,
                    'user' => $transfer->getUser(),
                ]);

            $this->mailer->send($email);
            
            $this->logger->warning('Suspicious activity notification sent to admin', [
                'transfer_id' => $transfer->getId(),
                'user_email' => $transfer->getUser()->getEmail(),
                'reason' => $reason
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send suspicious activity notification', [
                'transfer_id' => $transfer->getId(),
                'reason' => $reason,
                'error' => $e->getMessage()
            ]);
        }
    }
}
