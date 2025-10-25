<?php

namespace App\Message;

class RibAddedNotificationMessage extends AbstractEmailMessage
{
    public function __construct(
        string $to,
        string $toName,
        private readonly string $accountName,
        private readonly string $maskedIban,
        private readonly string $bankName,
        private readonly string $firstName,
        private readonly ?string $companyPhone = null,
        private readonly ?string $companyEmail = null
    ) {
        parent::__construct(
            to: $to,
            subject: 'email_rib_added_notification.title',
            template: 'email/rib_added_notification.html.twig',
            context: [
                'firstName' => $firstName,
                'accountName' => $accountName,
                'maskedIban' => $maskedIban,
                'bankName' => $bankName,
                'addedDate' => new \DateTime(),
                'companyPhone' => $companyPhone,
                'companyEmail' => $companyEmail,
            ],
            toName: $toName,
            translationDomain: 'email_rib_added_notification'
        );
    }

    public function getAccountName(): string
    {
        return $this->accountName;
    }

    public function getMaskedIban(): string
    {
        return $this->maskedIban;
    }

    public function getBankName(): string
    {
        return $this->bankName;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getCompanyPhone(): ?string
    {
        return $this->companyPhone;
    }

    public function getCompanyEmail(): ?string
    {
        return $this->companyEmail;
    }
}
