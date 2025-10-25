<?php

namespace App\Service;

use App\DTO\CreditApplicationDTO;
use App\Entity\CreditApplication;
use App\Enum\CreditApplicationStatusEnum;

/**
 * Service pour mapper les données entre DTO et entités
 */
class CreditApplicationMapperService
{
    /**
     * Crée une entité CreditApplication à partir d'un DTO
     */
    public function mapDtoToEntity(CreditApplicationDTO $dto): CreditApplication
    {
        $creditApplication = new CreditApplication();
        
        // Informations personnelles
        $creditApplication->setFirstName($dto->firstName)
                         ->setLastName($dto->lastName)
                         ->setEmail($dto->email)
                         ->setPhone($dto->phone)
                         ->setBirthDate($dto->birthDate)
                         ->setNationality($dto->nationality)
                         ->setMaritalStatus($dto->maritalStatus)
                         ->setDependents($dto->dependents);

        // Adresse
        $creditApplication->setAddress($dto->address)
                         ->setCity($dto->city)
                         ->setPostalCode($dto->postalCode)
                         ->setCountry($dto->country)
                         ->setHousingType($dto->housingType);

        // Situation financière
        $creditApplication->setMonthlyIncome((string) $dto->monthlyIncome)
                         ->setEmploymentType($dto->employmentType)
                         ->setEmployer($dto->employer)
                         ->setJobTitle($dto->jobTitle)
                         ->setEmploymentStartDate($dto->employmentStartDate)
                         ->setMonthlyExpenses((string) $dto->monthlyExpenses)
                         ->setExistingLoans($dto->existingLoans ? (string) $dto->existingLoans : null)
                         ->setBankName('Non renseigné'); // Valeur par défaut temporaire

        // Données du crédit
        $creditApplication->setLoanAmount((string) $dto->loanAmount)
                         ->setDuration($dto->duration)
                         ->setDurationUnit($dto->durationUnit)
                         ->setCreditType($dto->creditType)
                         ->setMonthlyPayment((string) $dto->monthlyPayment)
                         ->setTotalCost((string) $dto->totalCost);

        // Consentements (valeurs par défaut car plus dans les formulaires)
        $creditApplication->setTermsAccepted($dto->termsAccepted ?? true)
                         ->setDataProcessingAccepted($dto->dataProcessingAccepted ?? true)
                         ->setMarketingAccepted($dto->marketingAccepted ?? false);

        // Métadonnées
        $creditApplication->setStatus(CreditApplicationStatusEnum::IN_PROGRESS)
                         ->setLocale($dto->locale)
                         ->setCreatedAt(new \DateTimeImmutable())
                         ->setUpdatedAt(new \DateTimeImmutable())
                         ->generateConfirmationHash();

        return $creditApplication;
    }

    /**
     * Mappe une entité CreditApplication vers un DTO
     */
    public function mapEntityToDto(CreditApplication $entity): CreditApplicationDTO
    {
        $dto = new CreditApplicationDTO();
        
        // Informations personnelles
        $dto->firstName = $entity->getFirstName();
        $dto->lastName = $entity->getLastName();
        $dto->email = $entity->getEmail();
        $dto->phone = $entity->getPhone();
        $dto->birthDate = $entity->getBirthDate();
        $dto->nationality = $entity->getNationality();
        $dto->maritalStatus = $entity->getMaritalStatus();
        $dto->dependents = $entity->getDependents();

        // Adresse
        $dto->address = $entity->getAddress();
        $dto->city = $entity->getCity();
        $dto->postalCode = $entity->getPostalCode();
        $dto->country = $entity->getCountry();
        $dto->housingType = $entity->getHousingType();

        // Situation financière
        $dto->monthlyIncome = (float) $entity->getMonthlyIncome();
        $dto->employmentType = $entity->getEmploymentType();
        $dto->employer = $entity->getEmployer();
        $dto->jobTitle = $entity->getJobTitle();
        $dto->employmentStartDate = $entity->getEmploymentStartDate();
        $dto->monthlyExpenses = (float) $entity->getMonthlyExpenses();
        $dto->existingLoans = $entity->getExistingLoans() ? (float) $entity->getExistingLoans() : null;

        // Données du crédit
        $dto->loanAmount = (float) $entity->getLoanAmount();
        $dto->duration = $entity->getDuration();
        $dto->durationUnit = $entity->getDurationUnit();
        $dto->creditType = $entity->getCreditType();
        $dto->monthlyPayment = (float) $entity->getMonthlyPayment();
        $dto->totalCost = (float) $entity->getTotalCost();

        // Consentements
        $dto->termsAccepted = $entity->isTermsAccepted();
        $dto->dataProcessingAccepted = $entity->isDataProcessingAccepted();
        $dto->marketingAccepted = $entity->isMarketingAccepted();

        // Métadonnées
        $dto->locale = $entity->getLocale();

        return $dto;
    }

    /**
     * Met à jour une entité existante avec les données du DTO
     */
    public function updateEntityFromDto(CreditApplication $entity, CreditApplicationDTO $dto): CreditApplication
    {
        // Informations personnelles
        $entity->setFirstName($dto->firstName)
               ->setLastName($dto->lastName)
               ->setEmail($dto->email)
               ->setPhone($dto->phone)
               ->setBirthDate($dto->birthDate)
               ->setNationality($dto->nationality)
               ->setMaritalStatus($dto->maritalStatus)
               ->setDependents($dto->dependents);

        // Adresse
        $entity->setAddress($dto->address)
               ->setCity($dto->city)
               ->setPostalCode($dto->postalCode)
               ->setCountry($dto->country)
               ->setHousingType($dto->housingType);

        // Situation financière
        $entity->setMonthlyIncome((string) $dto->monthlyIncome)
               ->setEmploymentType($dto->employmentType)
               ->setEmployer($dto->employer)
               ->setJobTitle($dto->jobTitle)
               ->setEmploymentStartDate($dto->employmentStartDate)
               ->setMonthlyExpenses((string) $dto->monthlyExpenses)
               ->setExistingLoans($dto->existingLoans ? (string) $dto->existingLoans : null);

        // Données du crédit
        $entity->setLoanAmount((string) $dto->loanAmount)
               ->setDuration($dto->duration)
               ->setDurationUnit($dto->durationUnit)
               ->setCreditType($dto->creditType)
               ->setMonthlyPayment((string) $dto->monthlyPayment)
               ->setTotalCost((string) $dto->totalCost);

        // Consentements
        $entity->setTermsAccepted($dto->termsAccepted)
               ->setDataProcessingAccepted($dto->dataProcessingAccepted)
               ->setMarketingAccepted($dto->marketingAccepted ?? false);

        // Mettre à jour la date de modification
        $entity->setUpdatedAt(new \DateTimeImmutable());

        return $entity;
    }
}
