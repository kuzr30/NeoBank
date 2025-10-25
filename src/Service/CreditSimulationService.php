<?php

namespace App\Service;

use App\Enum\CreditTypeEnum;
use App\Enum\DurationUnitEnum;

class CreditSimulationService
{
    public function calculateMonthlyPayment(
        float $amount,
        CreditTypeEnum $creditType,
        int $duration,
        DurationUnitEnum $durationUnit
    ): array {
        $rate = $creditType->getRate();
        $durationInMonths = $durationUnit->toMonths($duration);
        
        // Calcul du taux mensuel
        $monthlyRate = ($rate / 100) / 12;
        
        // Calcul de la mensualité avec la formule standard
        if ($monthlyRate > 0) {
            $monthlyPayment = $amount * ($monthlyRate * pow(1 + $monthlyRate, $durationInMonths)) 
                / (pow(1 + $monthlyRate, $durationInMonths) - 1);
        } else {
            $monthlyPayment = $amount / $durationInMonths;
        }
        
        $totalAmount = $monthlyPayment * $durationInMonths;
        $totalInterest = $totalAmount - $amount;
        
        return [
            'monthlyPayment' => round($monthlyPayment, 2),
            'totalAmount' => round($totalAmount, 2),
            'totalInterest' => round($totalInterest, 2),
            'rate' => $rate,
            'durationInMonths' => $durationInMonths,
            'creditType' => $creditType,
            'originalAmount' => $amount
        ];
    }

    public function generateAmortizationTable(
        float $amount,
        CreditTypeEnum $creditType,
        int $duration,
        DurationUnitEnum $durationUnit,
        int $maxRows = 12
    ): array {
        $simulation = $this->calculateMonthlyPayment($amount, $creditType, $duration, $durationUnit);
        $monthlyPayment = $simulation['monthlyPayment'];
        $monthlyRate = ($creditType->getRate() / 100) / 12;
        $durationInMonths = $simulation['durationInMonths'];
        
        $remainingCapital = $amount;
        $amortizationTable = [];
        $displayRows = min($maxRows, $durationInMonths);
        
        for ($month = 1; $month <= $displayRows; $month++) {
            $interestPayment = $remainingCapital * $monthlyRate;
            $capitalPayment = $monthlyPayment - $interestPayment;
            $remainingCapital -= $capitalPayment;
            
            $amortizationTable[] = [
                'month' => $month,
                'monthlyPayment' => round($monthlyPayment, 2),
                'capitalPayment' => round($capitalPayment, 2),
                'interestPayment' => round($interestPayment, 2),
                'remainingCapital' => round(max(0, $remainingCapital), 2)
            ];
        }
        
        return $amortizationTable;
    }
}
