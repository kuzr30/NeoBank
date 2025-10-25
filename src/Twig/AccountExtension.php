<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AccountExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('account_type_label', [$this, 'getAccountTypeLabel']),
        ];
    }

    public function getAccountTypeLabel(string $type): string
    {
        return match($type) {
            'checking' => 'Compte Chèques',
            'savings' => 'Compte Épargne',
            'business' => 'Compte Professionnel',
            'investment' => 'Compte Investissement',
            'loan' => 'Compte Crédit',
            default => 'Compte Bancaire'
        };
    }
}
