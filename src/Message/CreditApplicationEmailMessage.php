<?php

namespace App\Message;

/**
 * Message pour l'email de confirmation de demande de crédit
 */
class CreditApplicationEmailMessage extends AbstractEmailMessage
{
    public function __construct(
        private readonly int $creditApplicationId,
        private readonly string $customerEmail,
        private readonly string $customerName,
        private readonly float $loanAmount,
        private readonly string $confirmationHash
    ) {
        parent::__construct(
            to: $this->customerEmail,
            subject: 'email_credit_application.title',
            template: 'email/credit_application_confirmation.html.twig',
            context: [
                'creditApplicationId' => $this->creditApplicationId,
                'customerName' => $this->customerName,
                'loanAmount' => $this->loanAmount,
                'confirmationHash' => $this->confirmationHash,
                'confirmationUrl' => null // Sera ajouté par le handler
            ],
            translationDomain: 'email_credit_application'
        );
    }

    public function getCreditApplicationId(): int
    {
        return $this->creditApplicationId;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getLoanAmount(): float
    {
        return $this->loanAmount;
    }

    public function getConfirmationHash(): string
    {
        return $this->confirmationHash;
    }
}
