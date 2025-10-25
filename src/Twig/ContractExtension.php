<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ContractExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('format_interest_rate_percent', [$this, 'formatInterestRatePercent']),
            new TwigFunction('get_correct_interest_rate', [$this, 'getCorrectInterestRate']),
            new TwigFunction('get_user_birthdate', [$this, 'getUserBirthdate']),
        ];
    }

    public function formatInterestRatePercent(float $rate): string
    {
        return number_format($rate, 2, ',', ' ');
    }

    public function getCorrectInterestRate($creditType): float
    {
        return match($creditType) {
            'personal' => 4.5,
            'auto' => 3.8,
            'mortgage' => 2.1,
            'professional' => 5.2,
            default => 4.5
        };
    }

    public function getUserBirthdate(string $email): ?\DateTimeInterface
    {
        // Cette fonction n'est plus utilisée, on retourne null
        return null;
    }
}
