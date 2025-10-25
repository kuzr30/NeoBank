<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class StringExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('str_pad', [$this, 'strPad']),
        ];
    }

    public function strPad(string $input, int $length, string $padString = '0', string $padType = 'left'): string
    {
        $padTypeConstant = match($padType) {
            'left' => STR_PAD_LEFT,
            'right' => STR_PAD_RIGHT,
            'both' => STR_PAD_BOTH,
            default => STR_PAD_LEFT,
        };
        
        return str_pad($input, $length, $padString, $padTypeConstant);
    }
}
