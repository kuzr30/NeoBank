<?php

namespace App\Message;

class InsuranceContractEmailMessage
{
    public function __construct(
        public readonly int $contratAssuranceId,
        public readonly string $customerEmail,
        public readonly string $customerName,
        public readonly string $contractPdf,
        public readonly string $contractFilename,
        public readonly string $contractNumber,
        public readonly string $insuranceType,
        public readonly string $insuranceTypeKey, // Clé de traduction pour l'objet
        public readonly float $monthlyPremium,
        public readonly string $activationDate,
        public readonly string $status,
        public readonly ?string $preferredLocale = null
    ) {}
}