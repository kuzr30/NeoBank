<?php

namespace App\Message;

class FundsDisbursedNotificationMessage
{
    public function __construct(
        public readonly int $creditApplicationId,
        public readonly string $customerEmail,
        public readonly string $customerName,
        public readonly string $contractNumber,
        public readonly float $loanAmount,
        public readonly float $totalFees,
        public readonly ?string $preferredLocale = null
    ) {}
}
