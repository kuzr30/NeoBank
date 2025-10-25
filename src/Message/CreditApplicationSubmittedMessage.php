<?php

namespace App\Message;

class CreditApplicationSubmittedMessage
{
    public function __construct(
        private readonly int $creditApplicationId,
        private readonly string $locale = 'fr'
    ) {}

    public function getCreditApplicationId(): int
    {
        return $this->creditApplicationId;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
