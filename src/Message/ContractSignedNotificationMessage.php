<?php

namespace App\Message;

class ContractSignedNotificationMessage
{
    public function __construct(
        public readonly int $creditApplicationId,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly string $contractNumber,
        public readonly ?string $signedContractPath,
    public readonly string $adminEmail,
    public readonly ?string $preferredLocale = null
    ) {}
}
