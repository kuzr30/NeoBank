<?php

namespace App\Twig;

use App\Enum\CreditTypeEnum;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CreditExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('credit_rate', [$this, 'getCreditRate']),
        ];
    }

    /**
     * Retourne le taux d'un type de crédit
     */
    public function getCreditRate(string $creditType): string
    {
        $enum = CreditTypeEnum::tryFrom($creditType);
        
        if ($enum === null) {
            return "2.9"; // Valeur par défaut si le type n'existe pas
        }
        
        return (string) $enum->getRate();
    }
}
