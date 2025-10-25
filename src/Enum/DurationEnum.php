<?php

namespace App\Enum;

enum DurationEnum: int
{
    case TWELVE_MONTHS = 12;
    case TWENTY_FOUR_MONTHS = 24;
    case THIRTY_SIX_MONTHS = 36;
    case FORTY_EIGHT_MONTHS = 48;
    case SIXTY_MONTHS = 60;
    case SEVENTY_TWO_MONTHS = 72;
    case EIGHTY_FOUR_MONTHS = 84;
    case NINETY_SIX_MONTHS = 96;
    case ONE_HUNDRED_TWENTY_MONTHS = 120;
    case ONE_HUNDRED_EIGHTY_MONTHS = 180;
    case TWO_HUNDRED_FORTY_MONTHS = 240;
    case THREE_HUNDRED_MONTHS = 300;

    public function getLabel(): string
    {
        return match($this) {
            self::TWELVE_MONTHS => 'credit_simulation.durations.twelve_months',
            self::TWENTY_FOUR_MONTHS => 'credit_simulation.durations.twenty_four_months',
            self::THIRTY_SIX_MONTHS => 'credit_simulation.durations.thirty_six_months',
            self::FORTY_EIGHT_MONTHS => 'credit_simulation.durations.forty_eight_months',
            self::SIXTY_MONTHS => 'credit_simulation.durations.sixty_months',
            self::SEVENTY_TWO_MONTHS => 'credit_simulation.durations.seventy_two_months',
            self::EIGHTY_FOUR_MONTHS => 'credit_simulation.durations.eighty_four_months',
            self::NINETY_SIX_MONTHS => 'credit_simulation.durations.ninety_six_months',
            self::ONE_HUNDRED_TWENTY_MONTHS => 'credit_simulation.durations.one_hundred_twenty_months',
            self::ONE_HUNDRED_EIGHTY_MONTHS => 'credit_simulation.durations.one_hundred_eighty_months',
            self::TWO_HUNDRED_FORTY_MONTHS => 'credit_simulation.durations.two_hundred_forty_months',
            self::THREE_HUNDRED_MONTHS => 'credit_simulation.durations.three_hundred_months',
        };
    }

    public static function getAvailableForCreditType(CreditTypeEnum $creditType): array
    {
        $maxDuration = $creditType->getMaxDuration();
        
        return array_filter(
            self::cases(),
            fn(self $duration) => $duration->value <= $maxDuration
        );
    }
}
