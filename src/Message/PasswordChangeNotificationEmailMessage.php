<?php

namespace App\Message;

/**
 * Message pour envoyer une notification de changement de mot de passe
 */
class PasswordChangeNotificationEmailMessage extends AbstractEmailMessage
{
    public function __construct(
        string $to,
        array $context = [],
        ?string $toName = null,
        ?string $fromEmail = null,
        ?string $fromName = null
    ) {
        parent::__construct(
            $to,
            'email_password_change_notification.title', // Utilisation de la clé de traduction
            'email/password_change_notification.html.twig',
            $context,
            $toName,
            $fromEmail,
            $fromName
        );
    }
}
