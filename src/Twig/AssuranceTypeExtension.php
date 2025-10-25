<?php

declare(strict_types=1);

namespace App\Twig;

use App\Enum\AssuranceType;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AssuranceTypeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('assurance_type_label', [$this, 'getAssuranceTypeLabel']),
        ];
    }

    public function getAssuranceTypeLabel(AssuranceType $type): string
    {
        return match ($type) {
            AssuranceType::AUTO => 'Assurance Automobile',
            AssuranceType::HABITATION => 'Assurance Habitation',
            AssuranceType::SANTE => 'Assurance Santé',
            AssuranceType::VIE => 'Assurance Vie',
            AssuranceType::PRET => 'Assurance Emprunteur',
            AssuranceType::VOYAGE => 'Assurance Voyage',
            AssuranceType::PRO => 'Assurance Professionnelle',
            AssuranceType::CYBER => 'Assurance Cyber-risques',
            AssuranceType::DECENNALE => 'Assurance Décennale',
            AssuranceType::RC => 'Responsabilité Civile',
            AssuranceType::FLOTTE => 'Assurance Flotte Automobile',
            AssuranceType::GARAGE => 'Assurance Garage',
            default => $type->value,
        };
    }
}