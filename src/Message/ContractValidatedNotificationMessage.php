<?php

namespace App\Message;

class ContractValidatedNotificationMessage
{
    public function __construct(
        public readonly int $creditApplicationId,
        public readonly string $customerEmail,
        public readonly string $customerName,
        public readonly string $contractNumber,
        public readonly float $loanAmount,
        public readonly ?string $preferredLocale = null
    ) {}
}
