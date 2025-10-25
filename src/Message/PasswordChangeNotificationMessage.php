<?php

namespace App\Message;

/**
 * Message pour envoyer une notification de changement de mot de passe
 */
class PasswordChangeNotificationMessage extends AbstractEmailMessage
{
    public function __construct(
        string $to,
        array $context = [],
        ?string $toName = null,
        ?string $fromEmail = null,
        ?string $fromName = null
    ) {
        parent::__construct(
            to: $to,
            subject: 'email_password_change.title',
            template: 'email/password_change_notification.html.twig',
            context: $context,
            toName: $toName,
            fromEmail: $fromEmail,
            fromName: $fromName,
            translationDomain: 'email_password_change'
        );
    }
}
