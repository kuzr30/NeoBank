<?php

namespace App\Message;

class ContractEmailMessage
{
    public function __construct(
        public readonly int $creditApplicationId,
        public readonly string $customerEmail,
        public readonly string $customerName,
        public readonly string $contractPdf,
        public readonly string $contractFilename,
    public readonly string $contractNumber,
    public readonly ?string $preferredLocale = null
    ) {}
}
