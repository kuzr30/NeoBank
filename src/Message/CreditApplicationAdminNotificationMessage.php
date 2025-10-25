<?php

namespace App\Message;

/**
 * Message pour la notification admin d'une nouvelle demande de crédit
 */
class CreditApplicationAdminNotificationMessage extends AbstractEmailMessage
{
    public function __construct(
        private readonly int $creditApplicationId,
        private readonly string $customerName,
        private readonly string $customerEmail,
        private readonly float $loanAmount,
        private readonly string $creditType,
        private readonly int $duration,
        string $adminEmail = null
    ) {
        parent::__construct(
            to: $adminEmail, // L'email admin sera injecté par le contrôleur
            subject: 'email_credit_application_admin.title',
            template: 'email/credit_application_admin_notification.html.twig',
            context: [
                'creditApplicationId' => $this->creditApplicationId,
                'customerName' => $this->customerName,
                'customerEmail' => $this->customerEmail,
                'loanAmount' => $this->loanAmount,
                'creditType' => $this->creditType,
                'duration' => $this->duration
            ],
            translationDomain: 'email_credit_application_admin'
        );
    }

    public function getCreditApplicationId(): int
    {
        return $this->creditApplicationId;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function getLoanAmount(): float
    {
        return $this->loanAmount;
    }

    public function getCreditType(): string
    {
        return $this->creditType;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }
}
