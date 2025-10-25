<?php

namespace App\Message;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Message pour l'email d'activation de compte
 */
class AccountActivationEmailMessage extends AbstractEmailMessage
{
    public function __construct(
        string $to,
        string $activationToken,
        string $userFirstName,
        UrlGeneratorInterface $urlGenerator,
        ?string $toName = null
    ) {
        // Générer l'URL absolue pour l'activation
        $activationUrl = $urlGenerator->generate(
            'app_account_activation',
            ['token' => $activationToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        parent::__construct(
            to: $to,
            subject: 'email_account_activation.title', // Clé complète
            template: 'email/account_activation.html.twig',
            context: [
                'activationToken' => $activationToken,
                'firstName' => $userFirstName,
                'activationUrl' => $activationUrl,
                'expirationTime' => '24 heures'
            ],
            toName: $toName,
            translationDomain: 'email_account_activation'
        );
    }
}
