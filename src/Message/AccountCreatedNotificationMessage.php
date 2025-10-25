<?php

namespace App\Message;

/**
 * Message pour la notification de création de compte
 */
class AccountCreatedNotificationMessage extends AbstractEmailMessage
{
    public function __construct(
        string $to,
        string $userFirstName,
        string $accountNumber,
        string $iban,
        string $countryCode,
        ?string $toName = null,
        ?string $locale = 'fr'
    ) {
        parent::__construct(
            to: $to,
            subject: 'email_notification_created_account.title',
            template: 'email/account_created.html.twig',
            context: [
                'firstName' => $userFirstName,
                'accountNumber' => $accountNumber,
                'formattedIban' => $this->formatIbanForDisplay($iban),
                'countryCode' => $countryCode,
                'locale' => $locale
            ],
            toName: $toName,
            translationDomain: 'email_notification_created_account'
        );
    }

    private function formatIbanForDisplay(string $iban): string
    {
        return trim(chunk_split($iban, 4, ' '));
    }
}
