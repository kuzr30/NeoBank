<?php

namespace App\Service;

use App\Enum\CreditTypeEnum;
use App\Enum\DurationUnitEnum;
use App\Service\ProfessionalTranslationService;

class CreditValidationService
{
    public const MIN_AMOUNT = 100;
    public const MAX_AMOUNT = 2000000;
    public const MIN_DURATION_MONTHS = 6;
    public const MAX_DURATION_MONTHS = 360; // 30 ans

    public function __construct(
        private ProfessionalTranslationService $translationService
    ) {
    }

    public function validateSimulationData(
        float $amount,
        CreditTypeEnum $creditType,
        int $duration,
        DurationUnitEnum $durationUnit
    ): array {
        $errors = [];
        $durationInMonths = $durationUnit->toMonths($duration);

        // Validation du montant
        if ($amount < self::MIN_AMOUNT) {
            $errors[] = sprintf(
                $this->translationService->tp('validation_errors.amount_min', [], 'credit_validation_service'),
                number_format(self::MIN_AMOUNT, 0, ',', ' ')
            );
        }

        if ($amount > self::MAX_AMOUNT) {
            $errors[] = sprintf(
                $this->translationService->tp('validation_errors.amount_max', [], 'credit_validation_service'),
                number_format(self::MAX_AMOUNT, 0, ',', ' ')
            );
        }

        // Validation de la durée
        if ($durationInMonths < self::MIN_DURATION_MONTHS) {
            $errors[] = sprintf(
                $this->translationService->tp('validation_errors.duration_min', [], 'credit_validation_service'),
                self::MIN_DURATION_MONTHS
            );
        }

        if ($durationInMonths > self::MAX_DURATION_MONTHS) {
            $errors[] = sprintf(
                $this->translationService->tp('validation_errors.duration_max', [], 'credit_validation_service'),
                self::MAX_DURATION_MONTHS,
                self::MAX_DURATION_MONTHS / 12
            );
        }

        // Validation spécifique par type de crédit
        $this->validateByType($amount, $creditType, $durationInMonths, $errors);

        return $errors;
    }

    private function validateByType(
        float $amount,
        CreditTypeEnum $creditType,
        int $durationInMonths,
        array &$errors
    ): void {
        switch ($creditType) {
            case CreditTypeEnum::IMMOBILIER:
                if ($amount < 10000) {
                    $errors[] = $this->translationService->tp('credit_type_errors.immobilier.amount_min', [], 'credit_validation_service');
                }
                if ($durationInMonths > 300) { // 25 ans
                    $errors[] = $this->translationService->tp('credit_type_errors.immobilier.duration_max', [], 'credit_validation_service');
                }
                break;

            case CreditTypeEnum::AUTO:
                if ($amount > 80000) {
                    $errors[] = $this->translationService->tp('credit_type_errors.auto.amount_max', [], 'credit_validation_service');
                }
                if ($durationInMonths > 84) { // 7 ans
                    $errors[] = $this->translationService->tp('credit_type_errors.auto.duration_max', [], 'credit_validation_service');
                }
                break;

            case CreditTypeEnum::CONSOMMATION:
                if ($amount > 75000) {
                    $errors[] = $this->translationService->tp('credit_type_errors.consommation.amount_max', [], 'credit_validation_service');
                }
                if ($durationInMonths > 84) { // 7 ans
                    $errors[] = $this->translationService->tp('credit_type_errors.consommation.duration_max', [], 'credit_validation_service');
                }
                break;

            case CreditTypeEnum::TRAVAUX:
                if ($amount > 150000) {
                    $errors[] = $this->translationService->tp('credit_type_errors.travaux.amount_max', [], 'credit_validation_service');
                }
                if ($durationInMonths > 120) { // 10 ans
                    $errors[] = $this->translationService->tp('credit_type_errors.travaux.duration_max', [], 'credit_validation_service');
                }
                break;

            case CreditTypeEnum::PROFESSIONNEL:
                if ($amount < 5000) {
                    $errors[] = $this->translationService->tp('credit_type_errors.professionnel.amount_min', [], 'credit_validation_service');
                }
                if ($durationInMonths > 180) { // 15 ans
                    $errors[] = $this->translationService->tp('credit_type_errors.professionnel.duration_max', [], 'credit_validation_service');
                }
                break;
        }
    }

    public function isValidAmount(float $amount): bool
    {
        return $amount >= self::MIN_AMOUNT && $amount <= self::MAX_AMOUNT;
    }

    public function isValidDuration(int $durationInMonths): bool
    {
        return $durationInMonths >= self::MIN_DURATION_MONTHS && 
               $durationInMonths <= self::MAX_DURATION_MONTHS;
    }
}
