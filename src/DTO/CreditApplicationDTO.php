<?php

namespace App\DTO;

use App\Enum\CreditTypeEnum;
use App\Enum\DurationUnitEnum;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour la demande de crédit multi-étapes
 * Étape 1: Données du crédit
 * Étape 2: Informations personnelles  
 * Étape 3: Situation financière
 */
class CreditApplicationDTO
{
    // === ÉTAPE 2: INFORMATIONS PERSONNELLES ===
    
    #[Assert\NotBlank(message: 'step1.fields.firstname.errors.required', groups: ['step2'])]
    #[Assert\Length(min: 2, max: 100, groups: ['step2'])]
    public ?string $firstName = null;

    #[Assert\NotBlank(message: 'step1.fields.lastname.errors.required', groups: ['step2'])]
    #[Assert\Length(min: 2, max: 100, groups: ['step2'])]
    public ?string $lastName = null;

    #[Assert\NotBlank(message: 'step1.fields.email.errors.required', groups: ['step2'])]
    #[Assert\Email(message: 'step1.fields.email.errors.invalid', groups: ['step2'])]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'step1.fields.phone.errors.required', groups: ['step2'])]
    #[Assert\Regex(
        pattern: '/^\+?[0-9\s\-]{5,15}$/', 
        message: 'step1.fields.phone.errors.invalid', 
        groups: ['step2']
    )]
    public ?string $phone = null;

    #[Assert\NotBlank(message: 'step1.fields.birth_date.errors.required', groups: ['step2'])]
    #[Assert\Range(
        min: '-100 years',
        max: '-18 years',
        notInRangeMessage: 'step1.fields.birth_date.errors.age_restriction',
        groups: ['step2']
    )]
    public ?\DateTimeInterface $birthDate = null;

    #[Assert\NotBlank(message: 'step1.fields.nationality.errors.required', groups: ['step2'])]
    public ?string $nationality = null;

    #[Assert\NotBlank(message: 'step1.fields.marital_status.errors.required', groups: ['step2'])]
    #[Assert\Choice(
        choices: ['single', 'married', 'divorced', 'widowed', 'cohabiting'], 
        message: 'step1.fields.marital_status.errors.invalid',
        groups: ['step2']
    )]
    public ?string $maritalStatus = null;

    #[Assert\NotNull(message: 'step1.fields.dependents.errors.required', groups: ['step2'])]
    #[Assert\Range(
        min: 0, 
        max: 20, 
        notInRangeMessage: 'step1.fields.dependents.errors.invalid_range',
        groups: ['step2']
    )]
    public ?int $dependents = null;

    // Adresse
    #[Assert\NotBlank(message: 'step1.address_section.address.errors.required', groups: ['step2'])]
    #[Assert\Length(min: 5, max: 255, groups: ['step2'])]
    public ?string $address = null;

    #[Assert\NotBlank(message: 'step1.address_section.city.errors.required', groups: ['step2'])]
    #[Assert\Length(min: 2, max: 100, groups: ['step2'])]
    public ?string $city = null;

    #[Assert\NotBlank(message: 'step1.address_section.postal_code.errors.required', groups: ['step2'])]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9]{1,7}$/', message: 'step1.address_section.postal_code.errors.invalid', groups: ['step2'])]
    public ?string $postalCode = null;

    #[Assert\NotBlank(message: 'step1.address_section.country.errors.required', groups: ['step2'])]
    public ?string $country = null;

    #[Assert\NotBlank(message: 'step1.address_section.housing_type.errors.required', groups: ['step2'])]
    #[Assert\Choice(
        choices: ['owner', 'tenant', 'family', 'other'], 
        message: 'step1.address_section.housing_type.errors.invalid',
        groups: ['step2']
    )]
    public ?string $housingType = null;

    // === ÉTAPE 3: SITUATION FINANCIÈRE ===

    #[Assert\NotBlank(message: 'step2.fields.monthly_income.errors.required', groups: ['step3'])]
    #[Assert\Range(min: 1000, minMessage: 'step2.fields.monthly_income.errors.min_value', groups: ['step3'])]
    public ?float $monthlyIncome = null;

    #[Assert\NotBlank(message: 'step2.fields.employment_type.errors.required', groups: ['step3'])]
    #[Assert\Choice(
        choices: ['employee', 'civil_servant', 'freelancer', 'entrepreneur', 'retired', 'unemployed'], 
        message: 'step2.fields.employment_type.errors.invalid',
        groups: ['step3']
    )]
    public ?string $employmentType = null;

    #[Assert\Length(max: 255, groups: ['step3'])]
    public ?string $employer = null;

    #[Assert\Length(max: 255, groups: ['step3'])]
    public ?string $jobTitle = null;

    public ?\DateTimeInterface $employmentStartDate = null;

    #[Assert\NotBlank(message: 'step2.fields.monthly_expenses.errors.required', groups: ['step3'])]
    #[Assert\Range(min: 0, minMessage: 'step2.fields.monthly_expenses.errors.min_value', groups: ['step3'])]
    public ?float $monthlyExpenses = null;

    #[Assert\Range(min: 0, minMessage: 'step2.fields.existing_loans.errors.min_value', groups: ['step3'])]
    public ?float $existingLoans = null;

    // === ÉTAPE 1: DONNÉES DU CRÉDIT ===

    #[Assert\NotBlank(message: 'step3.credit_configuration.loan_amount.errors.required', groups: ['step1'])]
    #[Assert\Range(
        min: 3000, 
        max: 30000000,
        notInRangeMessage: 'step3.credit_configuration.loan_amount.errors.invalid_range',
        groups: ['step1']
    )]
    public ?float $loanAmount = null;

    #[Assert\NotBlank(message: 'step3.credit_configuration.duration.errors.required', groups: ['step1'])]
    #[Assert\Range(
        min: 2, 
        notInRangeMessage: 'step3.credit_configuration.duration.errors.min_value',
        groups: ['step1']
    )]
    public ?int $duration = null;

    public ?DurationUnitEnum $durationUnit = DurationUnitEnum::MONTHS;

    public ?CreditTypeEnum $creditType = null;

    public ?float $monthlyPayment = null;

    public ?float $totalCost = null;

    // === CONSENTEMENTS ===

    #[Assert\IsTrue(message: 'step3.consent.terms_accepted.errors.required', groups: ['step3'])]
    public ?bool $termsAccepted = null;

    #[Assert\IsTrue(message: 'step3.consent.data_processing_accepted.errors.required', groups: ['step3'])]
    public ?bool $dataProcessingAccepted = null;

    public ?bool $marketingAccepted = false;

    // === MÉTADONNÉES ===

    public ?string $locale = null;

    // === MÉTHODES UTILITAIRES ===

    /**
     * Calcule le paiement mensuel basé sur le montant, la durée et le type de crédit
     */
    public function calculateMonthlyPayment(): ?float
    {
        if (!$this->loanAmount || !$this->duration || !$this->creditType) {
            return null;
        }

        $annualRate = $this->creditType->getRate() / 100;
        $monthlyRate = $annualRate / 12;
        $durationInMonths = $this->duration; // La durée est toujours en mois maintenant

        if ($monthlyRate == 0) {
            return $this->loanAmount / $durationInMonths;
        }

        $monthlyPayment = $this->loanAmount * 
            ($monthlyRate * pow(1 + $monthlyRate, $durationInMonths)) / 
            (pow(1 + $monthlyRate, $durationInMonths) - 1);

        $this->monthlyPayment = round($monthlyPayment, 2);
        return $this->monthlyPayment;
    }

    /**
     * Calcule le coût total du crédit
     */
    public function calculateTotalCost(): ?float
    {
        if (!$this->monthlyPayment || !$this->duration) {
            return null;
        }

        $durationInMonths = $this->duration; // La durée est toujours en mois maintenant

        $this->totalCost = round($this->monthlyPayment * $durationInMonths, 2);
        return $this->totalCost;
    }

    /**
     * Retourne le nom complet
     */
    public function getFullName(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }

    /**
     * Calcule l'âge
     */
    public function getAge(): ?int
    {
        if (!$this->birthDate) {
            return null;
        }
        
        return (new \DateTime())->diff($this->birthDate)->y;
    }

    /**
     * Vérifie si une étape est complète
     */
    public function isStepComplete(int $step): bool
    {
        return match($step) {
            1 => $this->isStep1Complete(), // Détails du crédit
            2 => $this->isStep2Complete(), // Infos personnelles  
            3 => $this->isStep3Complete(), // Situation financière + consentements
            default => false
        };
    }

    private function isStep1Complete(): bool
    {
        // Étape 1 : Détails du crédit (anciennement étape 3)
        return !empty($this->loanAmount) && 
               !empty($this->duration) && 
               !empty($this->creditType);
    }

    private function isStep2Complete(): bool
    {
        // Étape 2 : Informations personnelles (anciennement étape 1)
        return !empty($this->firstName) && 
               !empty($this->lastName) && 
               !empty($this->email) && 
               !empty($this->phone) && 
               !empty($this->birthDate) && 
               !empty($this->address) && 
               !empty($this->city) && 
               !empty($this->postalCode);
    }

    private function isStep3Complete(): bool
    {
        // Étape 3 : Situation financière (sans consentements)
        return !empty($this->employmentType) && 
               !empty($this->monthlyIncome) && 
               !empty($this->monthlyExpenses);
    }

    /**
     * Retourne le pourcentage de completion global
     */
    public function getCompletionPercentage(): int
    {
        $completedSteps = 0;
        
        if ($this->isStep1Complete()) $completedSteps++;
        if ($this->isStep2Complete()) $completedSteps++;
        if ($this->isStep3Complete()) $completedSteps++;
        
        return round(($completedSteps / 3) * 100);
    }
}
