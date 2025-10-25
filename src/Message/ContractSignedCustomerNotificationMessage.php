<?php

namespace App\Message;

class ContractSignedCustomerNotificationMessage
{
    public function __construct(
        public readonly int $creditApplicationId,
        public readonly string $customerEmail,
        public readonly string $customerName,
        public readonly float $loanAmount,
        public readonly ?string $preferredLocale = null
    ) {}
}