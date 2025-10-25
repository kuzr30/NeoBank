<?php

namespace App\Message;

/**
 * Message pour les emails génériques
 */
class GenericEmailMessage extends AbstractEmailMessage
{
    public function __construct(
        string $to,
        string $subject,
        string $template,
        array $context = [],
        ?string $toName = null,
        ?string $fromEmail = null,
        ?string $fromName = null
    ) {
        parent::__construct(
            to: $to,
            subject: $subject,
            template: $template,
            context: $context,
            toName: $toName,
            fromEmail: $fromEmail,
            fromName: $fromName
        );
    }
}
