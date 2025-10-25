<?php

namespace App\Message;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Message pour l'email de bienvenue
 */
class WelcomeEmailMessage extends AbstractEmailMessage
{
    public function __construct(
        string $to,
        string $userFirstName,
        UrlGeneratorInterface $urlGenerator,
        ?string $toName = null,
        ?string $locale = 'fr'
    ) {
        // Générer l'URL absolue pour la connexion avec la locale appropriée
        $loginUrl = $urlGenerator->generate(
            'app_login',
            ['_locale' => $locale ?? 'fr'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        parent::__construct(
            to: $to,
            subject: 'email_welcome.title',
            template: 'email/welcome.html.twig',
            context: [
                'firstName' => $userFirstName,
                'loginUrl' => $loginUrl
            ],
            toName: $toName,
            translationDomain: 'email_welcome'
        );
    }
}
