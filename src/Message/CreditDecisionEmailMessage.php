<?php

namespace App\Message;

class CreditDecisionEmailMessage
{
    public function __construct(
        public readonly int $creditApplicationId,
        public readonly string $customerEmail,
        public readonly string $customerName,
        public readonly bool $approved,
        public readonly string $loanAmount,
    public readonly ?string $contractNumber = null,
    public readonly ?string $preferredLocale = null
    ) {}
}
