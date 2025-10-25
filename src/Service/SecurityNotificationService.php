<?php

namespace App\Service;

use App\Entity\User;
use App\Message\PasswordChangeNotificationEmailMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour gérer les notifications de sécurité
 * Respecte le principe Single Responsibility
 */
class SecurityNotificationService
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private TranslatorInterface $translator,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Envoie une notification de changement de mot de passe
     */
    public function sendPasswordChangeNotification(User $user): void
    {
        try {
            $subject = $this->translator->trans(
                'security.password_change.subject',
                [],
                'messages',
                $user->getLanguage() ?? 'fr'
            );
            
            $context = [
                'user' => $user,
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'changeDate' => new \DateTimeImmutable(),
                'locale' => $user->getLanguage() ?? 'fr',
                'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            ];

            $message = new PasswordChangeNotificationEmailMessage(
                $user->getEmail(),
                $subject,
                $context,
                $user->getFirstName() . ' ' . $user->getLastName()
            );

            $this->messageBus->dispatch($message);

            $this->logger->info('Notification de changement de mot de passe programmée', [
                'userId' => $user->getId(),
                'email' => $user->getEmail()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la programmation de la notification de changement de mot de passe', [
                'userId' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage()
            ]);
            // Ne pas faire échouer le changement de mot de passe pour un problème d'email
        }
    }
}
