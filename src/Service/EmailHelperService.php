<?php

namespace App\Service;

use App\Message\WelcomeEmailMessage;
use App\Message\PasswordResetEmailMessage;
use App\Message\AccountActivationEmailMessage;
use App\Message\BankingNotificationEmailMessage;
use App\Message\GenericEmailMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;

/**
 * Service helper pour simplifier l'envoi d'emails
 */
class EmailHelperService
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private EmailService $emailService,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Envoie un email de bienvenue de façon asynchrone
     */
    public function sendWelcomeEmail(string $to, string $firstName, ?string $toName = null, ?string $locale = 'fr'): void
    {
        try {
            $message = new WelcomeEmailMessage($to, $firstName, $this->urlGenerator, $toName, $locale);
            $this->messageBus->dispatch($message);
            
            $this->logger->info('Email de bienvenue mis en file d\'attente', [
                'to' => $to,
                'firstName' => $firstName
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise en file de l\'email de bienvenue', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Envoie un email d'activation de compte de façon asynchrone
     */
    public function sendAccountActivationEmail(
        string $to, 
        string $activationToken, 
        string $firstName, 
        ?string $toName = null
    ): void {
        try {
            $message = new AccountActivationEmailMessage(
                $to, 
                $activationToken, 
                $firstName, 
                $this->urlGenerator,
                $toName
            );
            $this->messageBus->dispatch($message);
            
            $this->logger->info('Email d\'activation mis en file d\'attente', [
                'to' => $to,
                'firstName' => $firstName
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise en file de l\'email d\'activation', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Envoie un email de réinitialisation de mot de passe de façon asynchrone
     */
    public function sendPasswordResetEmail(
        string $to, 
        string $resetToken, 
        string $firstName, 
        ?string $toName = null
    ): void {
        try {
            $message = new PasswordResetEmailMessage(
                $to, 
                $resetToken, 
                $firstName, 
                $this->urlGenerator,
                $toName
            );
            $this->messageBus->dispatch($message);
            
            $this->logger->info('Email de réinitialisation mis en file d\'attente', [
                'to' => $to,
                'firstName' => $firstName
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise en file de l\'email de réinitialisation', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Envoie une notification bancaire de façon asynchrone
     */
    public function sendBankingNotification(
        string $to,
        string $notificationType,
        array $notificationData,
        string $firstName,
        ?string $toName = null
    ): void {
        try {
            $message = new BankingNotificationEmailMessage(
                $to, 
                $notificationType, 
                $notificationData, 
                $firstName, 
                $toName
            );
            $this->messageBus->dispatch($message);
            
            $this->logger->info('Notification bancaire mise en file d\'attente', [
                'to' => $to,
                'type' => $notificationType,
                'firstName' => $firstName
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise en file de la notification bancaire', [
                'to' => $to,
                'type' => $notificationType,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Envoie un email générique de façon asynchrone
     */
    public function sendGenericEmail(
        string $to,
        string $subject,
        string $template,
        array $context = [],
        ?string $toName = null,
        ?string $fromEmail = null,
        ?string $fromName = null
    ): void {
        try {
            $message = new GenericEmailMessage(
                $to, 
                $subject, 
                $template, 
                $context, 
                $toName, 
                $fromEmail, 
                $fromName
            );
            $this->messageBus->dispatch($message);
            
            $this->logger->info('Email générique mis en file d\'attente', [
                'to' => $to,
                'subject' => $subject,
                'template' => $template
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise en file de l\'email générique', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Envoie un email de façon synchrone (immédiate)
     */
    public function sendEmailSync(
        string $to,
        string $subject,
        string $template,
        array $context = [],
        ?string $toName = null,
        ?string $fromEmail = null,
        ?string $fromName = null
    ): bool {
        try {
            return $this->emailService->sendEmail(
                $to, 
                $subject, 
                $template, 
                $context, 
                $toName, 
                $fromEmail, 
                $fromName
            );
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi synchrone d\'email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Notifications bancaires prédéfinies
     */
    public function sendTransactionNotification(
        string $to,
        string $firstName,
        array $transactionData,
        ?string $toName = null
    ): void {
        $this->sendBankingNotification(
            $to,
            'transaction',
            $transactionData,
            $firstName,
            $toName
        );
    }

    public function sendSecurityAlert(
        string $to,
        string $firstName,
        array $securityData,
        ?string $toName = null
    ): void {
        $this->sendBankingNotification(
            $to,
            'security',
            $securityData,
            $firstName,
            $toName
        );
    }

    public function sendMonthlyStatement(
        string $to,
        string $firstName,
        array $statementData,
        ?string $toName = null
    ): void {
        $this->sendBankingNotification(
            $to,
            'statement',
            $statementData,
            $firstName,
            $toName
        );
    }

    public function sendCreditApproval(
        string $to,
        string $firstName,
        array $creditData,
        ?string $toName = null
    ): void {
        $this->sendBankingNotification(
            $to,
            'credit_approval',
            $creditData,
            $firstName,
            $toName
        );
    }

    public function sendCreditRejection(
        string $to,
        string $firstName,
        array $rejectionData,
        ?string $toName = null
    ): void {
        $this->sendBankingNotification(
            $to,
            'credit_rejection',
            $rejectionData,
            $firstName,
            $toName
        );
    }
}
