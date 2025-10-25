<?php

namespace App\Enum;

enum DurationUnitEnum: string
{
    case MONTHS = 'months';
    case YEARS = 'years';

    public function getLabel(): string
    {
        return match($this) {
            self::MONTHS => 'credit_simulation.duration_units.months',
            self::YEARS => 'credit_simulation.duration_units.years',
        };
    }

    public function toMonths(int $duration): int
    {
        return match($this) {
            self::MONTHS => $duration,
            self::YEARS => $duration * 12,
        };
    }
}
