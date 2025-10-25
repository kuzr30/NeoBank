<?php

namespace App\Message;

/**
 * Message pour envoyer un email de validation d'adresse email
 */
class EmailVerificationMessage extends AbstractEmailMessage
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
            subject: 'email_email_verification.title', // Utilisation de la clé de traduction
            template: 'email/email_verification.html.twig',
            context: $context,
            toName: $toName,
            fromEmail: $fromEmail,
            fromName: $fromName,
            translationDomain: 'email_email_verification'
        );
    }
}
