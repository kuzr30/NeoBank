<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Message pour l'envoi d'email d'approbation de demande de devis
 */
class DevisApprovalEmailMessage
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