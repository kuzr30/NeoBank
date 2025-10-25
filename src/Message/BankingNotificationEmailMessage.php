<?php

namespace App\Message;

/**
 * Message pour les notifications bancaires
 */
class BankingNotificationEmailMessage extends AbstractEmailMessage
{
    public function __construct(
        string $to,
        string $notificationType,
        array $notificationData,
        string $userFirstName,
        ?string $toName = null
    ) {
        parent::__construct(
            to: $to,
            subject: 'email_banking_notification.title',
            template: 'email/banking_notification.html.twig',
            context: array_merge($notificationData, [
                'firstName' => $userFirstName,
                'notificationType' => $notificationType,
                'dashboardUrl' => '/dashboard'
            ]),
            toName: $toName,
            translationDomain: 'email_banking_notification'
        );
    }
}
