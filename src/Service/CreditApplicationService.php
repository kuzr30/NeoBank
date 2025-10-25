<?php

namespace App\Service;

use App\Entity\CreditApplication;
use App\Enum\CreditApplicationStatusEnum;
use App\Repository\CreditApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;

class CreditApplicationService
{
    public function __construct(
        private CreditApplicationRepository $repository,
        private EntityManagerInterface $entityManager,
        private ProfessionalTranslationService $translationService
    ) {}

    public function createApplication(array $data): CreditApplication
    {
        $application = new CreditApplication();
        
        // Hydratation des données
        $this->hydrateApplication($application, $data);
        
        // Calculs automatiques
        $this->calculateTotalCost($application);
        
        // Métadonnées
        $application->setStatus(CreditApplicationStatusEnum::IN_PROGRESS);
        $application->setCreatedAt(new \DateTimeImmutable());
        $application->setUpdatedAt(new \DateTimeImmutable());
        $application->generateConfirmationHash();
        
        return $application;
    }

    public function saveApplication(CreditApplication $application): void
    {
        $application->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($application);
        $this->entityManager->flush();
    }

    public function updateStatus(CreditApplication $application, CreditApplicationStatusEnum $status, ?string $notes = null): void
    {
        $application->setStatus($status);
        $application->setUpdatedAt(new \DateTimeImmutable());
        
        if ($notes) {
            $application->setNotes($notes);
        }
        
        $this->saveApplication($application);
    }

    public function calculateDebtToIncomeRatio(CreditApplication $application): float
    {
        $monthlyIncome = (float) $application->getMonthlyIncome();
        $monthlyExpenses = (float) $application->getMonthlyExpenses();
        $existingLoans = (float) ($application->getExistingLoans() ?? 0);
        $newLoanPayment = (float) $application->getMonthlyPayment();
        
        $totalDebt = $monthlyExpenses + $existingLoans + $newLoanPayment;
        
        if ($monthlyIncome <= 0) {
            return 100; // Ratio maximum si pas de revenus
        }
        
        return ($totalDebt / $monthlyIncome) * 100;
    }

    public function isEligible(CreditApplication $application): array
    {
        $errors = [];
        
        // Vérification de l'âge
        $age = $application->getAge();
        if ($age < 18) {
            $errors[] = $this->translationService->tp('validation_errors.age_minor', [], 'credit_application_service');
        }
        if ($age > 75) {
            $errors[] = $this->translationService->tp('validation_errors.age_maximum', [], 'credit_application_service');
        }
        
        // Vérification du ratio d'endettement
        $debtRatio = $this->calculateDebtToIncomeRatio($application);
        if ($debtRatio > 33) {
            $errors[] = $this->translationService->tp('validation_errors.debt_ratio_exceeded', [
                'debtRatio' => round($debtRatio, 1)
            ], 'credit_application_service');
        }
        
        // Vérification du revenu minimum
        $monthlyIncome = (float) $application->getMonthlyIncome();
        if ($monthlyIncome < 1000) {
            $errors[] = $this->translationService->tp('validation_errors.minimum_income', [], 'credit_application_service');
        }
        
        // Vérification de la stabilité professionnelle
        if ($application->getEmploymentType() === 'unemployed') {
            $errors[] = $this->translationService->tp('validation_errors.employment_required', [], 'credit_application_service');
        }
        
        return [
            'eligible' => empty($errors),
            'errors' => $errors,
            'debtRatio' => $debtRatio
        ];
    }

    public function getApplicationsByStatus(CreditApplicationStatusEnum $status): array
    {
        return $this->repository->findBy(['status' => $status]);
    }

    public function getRecentApplications(int $limit = 10): array
    {
        return $this->repository->findRecent($limit);
    }

    public function getStatistics(): array
    {
        return $this->repository->getStatusStatistics();
    }

    private function hydrateApplication(CreditApplication $application, array $data): void
    {
        // Informations personnelles
        if (isset($data['firstName'])) $application->setFirstName($data['firstName']);
        if (isset($data['lastName'])) $application->setLastName($data['lastName']);
        if (isset($data['email'])) $application->setEmail($data['email']);
        if (isset($data['phone'])) $application->setPhone($data['phone']);
        if (isset($data['birthDate'])) $application->setBirthDate(new \DateTime($data['birthDate']));
        if (isset($data['nationality'])) $application->setNationality($data['nationality']);
        if (isset($data['maritalStatus'])) $application->setMaritalStatus($data['maritalStatus']);
        if (isset($data['dependents'])) $application->setDependents($data['dependents']);
        
        // Adresse
        if (isset($data['address'])) $application->setAddress($data['address']);
        if (isset($data['city'])) $application->setCity($data['city']);
        if (isset($data['postalCode'])) $application->setPostalCode($data['postalCode']);
        if (isset($data['country'])) $application->setCountry($data['country']);
        if (isset($data['housingType'])) $application->setHousingType($data['housingType']);
        
        // Situation professionnelle
        if (isset($data['monthlyIncome'])) $application->setMonthlyIncome($data['monthlyIncome']);
        if (isset($data['employmentType'])) $application->setEmploymentType($data['employmentType']);
        if (isset($data['employer'])) $application->setEmployer($data['employer']);
        if (isset($data['jobTitle'])) $application->setJobTitle($data['jobTitle']);
        if (isset($data['employmentStartDate'])) $application->setEmploymentStartDate(new \DateTime($data['employmentStartDate']));
        
        // Situation financière
        if (isset($data['monthlyExpenses'])) $application->setMonthlyExpenses($data['monthlyExpenses']);
        if (isset($data['existingLoans'])) $application->setExistingLoans($data['existingLoans']);
        if (isset($data['bankName'])) $application->setBankName($data['bankName']);
        if (isset($data['accountNumber'])) $application->setAccountNumber($data['accountNumber']);
        
        // Consentements
        if (isset($data['termsAccepted'])) $application->setTermsAccepted($data['termsAccepted']);
        if (isset($data['dataProcessingAccepted'])) $application->setDataProcessingAccepted($data['dataProcessingAccepted']);
        if (isset($data['marketingAccepted'])) $application->setMarketingAccepted($data['marketingAccepted']);
    }

    private function calculateTotalCost(CreditApplication $application): void
    {
        if ($application->getMonthlyPayment() && $application->getDuration() && $application->getLoanAmount()) {
            $totalPaid = (float) $application->getMonthlyPayment() * $application->getDuration();
            $totalCost = $totalPaid - (float) $application->getLoanAmount();
            $application->setTotalCost((string) $totalCost);
        }
    }
}
