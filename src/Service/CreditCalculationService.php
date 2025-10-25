<?php

namespace App\Service;

use App\Enum\CreditTypeEnum;
use App\Enum\DurationUnitEnum;

class CreditCalculationService
{
    /**
     * Calcule la simulation de crédit
     * 
     * @param float $amount Montant du crédit
     * @param int $duration Durée du crédit
     * @param DurationUnitEnum $durationUnit Unité de durée (mois ou années)
     * @param CreditTypeEnum $creditType Type de crédit
     * @return array
     */
    public function calculateLoan(
        float $amount,
        int $duration,
        DurationUnitEnum $durationUnit,
        CreditTypeEnum $creditType
    ): array {
        // Convertir la durée en mois
        $durationInMonths = $durationUnit->toMonths($duration);
        
        // Obtenir le taux d'intérêt
        $annualRate = $creditType->getRate();
        $monthlyRate = $annualRate / 100 / 12;
        
        // Calcul de la mensualité avec la formule des annuités constantes
        if ($monthlyRate > 0) {
            $monthlyPayment = $amount * ($monthlyRate * pow(1 + $monthlyRate, $durationInMonths)) 
                            / (pow(1 + $monthlyRate, $durationInMonths) - 1);
        } else {
            $monthlyPayment = $amount / $durationInMonths;
        }
        
        // Calculs dérivés
        $totalAmount = $monthlyPayment * $durationInMonths;
        $totalInterest = $totalAmount - $amount;
        
        return [
            'monthly_payment' => round($monthlyPayment, 2),
            'total_amount' => round($totalAmount, 2),
            'total_interest' => round($totalInterest, 2),
            'interest_rate' => $annualRate,
            'duration_months' => $durationInMonths,
            'principal' => $amount
        ];
    }
}
