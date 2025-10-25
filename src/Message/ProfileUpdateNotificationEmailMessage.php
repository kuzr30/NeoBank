<?php

namespace App\Message;

/**
 * Message pour envoyer une notification de mise à jour de profil
 */
class ProfileUpdateNotificationEmailMessage extends AbstractEmailMessage
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
            subject: 'email_profile_update_notification.title',
            template: 'email/profile_update_notification.html.twig',
            context: $context,
            toName: $toName,
            fromEmail: $fromEmail,
            fromName: $fromName,
            translationDomain: 'email_profile_update_notification'
        );
    }
}
