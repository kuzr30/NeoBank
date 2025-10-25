<?php

namespace App\Message;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Message pour l'email de réinitialisation de mot de passe
 */
class PasswordResetEmailMessage extends AbstractEmailMessage
{
    public function __construct(
        string $to,
        string $resetToken,
        string $userFirstName,
        UrlGeneratorInterface $urlGenerator,
        ?string $toName = null
    ) {
        // Générer l'URL absolue pour la réinitialisation
        $resetUrl = $urlGenerator->generate(
            'app_reset_password',
            ['token' => $resetToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        parent::__construct(
            to: $to,
            subject: 'email_password_reset.title',
            template: 'email/password_reset.html.twig',
            context: [
                'resetToken' => $resetToken,
                'firstName' => $userFirstName,
                'resetUrl' => $resetUrl,
                'expirationTime' => '1 heure'
            ],
            toName: $toName,
            translationDomain: 'email_password_reset'
        );
    }
}
