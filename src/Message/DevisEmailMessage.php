<?php

declare(strict_types=1);

namespace App\Message;

class DevisEmailMessage
{
    public function __construct(
        private readonly int $demandeDevisId,
        private readonly string $locale = 'fr'
    ) {}

    public function getDemandeDevisId(): int
    {
        return $this->demandeDevisId;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
