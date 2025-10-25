<?php

namespace App\Entity;

use App\Enum\CreditTypeEnum;
use App\Enum\DurationUnitEnum;
use App\Enum\CreditApplicationStatusEnum;
use App\Repository\CreditApplicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CreditApplicationRepository::class)]
#[ORM\Table(name: 'credit_applications')]
class CreditApplication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32, unique: true)]
    private ?string $confirmationHash = null;

    #[ORM\Column(length: 12, unique: true)]
    private ?string $referenceNumber = null;

    // === INFORMATIONS PERSONNELLES ===
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'L\'email doit être valide')]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le numéro de téléphone est obligatoire')]
    #[Assert\Regex(pattern: '/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/', message: 'Format de téléphone invalide')]
    private ?string $phone = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de naissance est obligatoire')]
    #[Assert\Range(
        min: '-100 years',
        max: '-18 years',
        notInRangeMessage: 'Vous devez être majeur pour faire une demande de crédit'
    )]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'La nationalité est obligatoire')]
    private ?string $nationality = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'La situation familiale est obligatoire')]
    #[Assert\Choice(choices: ['single', 'married', 'divorced', 'widowed', 'cohabiting'], message: 'Situation familiale invalide')]
    private ?string $maritalStatus = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le nombre de personnes à charge est obligatoire')]
    #[Assert\Range(min: 0, max: 20, notInRangeMessage: 'Le nombre de personnes à charge doit être entre 0 et 20')]
    private ?int $dependents = null;

    // === ADRESSE ===
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'L\'adresse est obligatoire')]
    #[Assert\Length(min: 5, max: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'La ville est obligatoire')]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $city = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'Le code postal est obligatoire')]
    #[Assert\Regex(pattern: '/^\d{5}$/', message: 'Le code postal doit contenir 5 chiffres')]
    private ?string $postalCode = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le pays est obligatoire')]
    private ?string $country = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type de logement est obligatoire')]
    #[Assert\Choice(choices: ['owner', 'tenant', 'family', 'other'], message: 'Type de logement invalide')]
    private ?string $housingType = null;

    // === SITUATION PROFESSIONNELLE ===
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le revenu mensuel est obligatoire')]
    #[Assert\Range(min: 1000, minMessage: 'Le revenu mensuel minimum est de 1000€')]
    private ?string $monthlyIncome = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type d\'emploi est obligatoire')]
    #[Assert\Choice(choices: ['employee', 'civil_servant', 'freelancer', 'entrepreneur', 'retired', 'unemployed'], message: 'Type d\'emploi invalide')]
    private ?string $employmentType = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $employer = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $jobTitle = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $employmentStartDate = null;

    // === SITUATION FINANCIÈRE ===
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Les charges mensuelles sont obligatoires')]
    #[Assert\Range(min: 0, minMessage: 'Les charges ne peuvent être négatives')]
    private ?string $monthlyExpenses = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\Range(min: 0, minMessage: 'Le montant ne peut être négatif')]
    private ?string $existingLoans = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de la banque est obligatoire')]
    #[Assert\Length(min: 2, max: 255)]
    private ?string $bankName = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $accountNumber = null;

    // === DONNÉES DU CRÉDIT ===
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Range(min: 1000, max: 500000, notInRangeMessage: 'Le montant du crédit doit être entre 1 000€ et 500 000€')]
    private ?string $loanAmount = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Range(min: 12, max: 360, notInRangeMessage: 'La durée doit être entre 12 et 360 mois')]
    private ?int $duration = null;

    #[ORM\Column(enumType: DurationUnitEnum::class)]
    private ?DurationUnitEnum $durationUnit = DurationUnitEnum::MONTHS;

    #[ORM\Column(enumType: CreditTypeEnum::class)]
    private ?CreditTypeEnum $creditType = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $monthlyPayment = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private ?string $totalCost = null;

    // === CONSENTEMENTS ===
    #[ORM\Column]
    #[Assert\IsTrue(message: 'Vous devez accepter les conditions générales')]
    private ?bool $termsAccepted = null;

    #[ORM\Column]
    #[Assert\IsTrue(message: 'Vous devez accepter le traitement de vos données personnelles')]
    private ?bool $dataProcessingAccepted = null;

    #[ORM\Column]
    private ?bool $marketingAccepted = null;

    // === MÉTADONNÉES ===
    #[ORM\Column(type: 'string', enumType: CreditApplicationStatusEnum::class)]
    private CreditApplicationStatusEnum $status = CreditApplicationStatusEnum::IN_PROGRESS;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $locale = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $contractPath = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $resendCount = 0;

    #[ORM\OneToMany(mappedBy: 'creditApplication', targetEntity: AmortizationSchedule::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $amortizationSchedules;

    #[ORM\OneToMany(mappedBy: 'creditApplication', targetEntity: ContractFee::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $contractFees;

    #[ORM\OneToMany(mappedBy: 'creditApplication', targetEntity: Loan::class)]
    private Collection $loans;

    public function __construct()
    {
        $this->amortizationSchedules = new ArrayCollection();
        $this->contractFees = new ArrayCollection();
        $this->loans = new ArrayCollection();
        $this->generateReferenceNumber();
    }

    // === GETTERS & SETTERS ===

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getNationality(): ?string
    {
        return $this->nationality;
    }

    public function setNationality(string $nationality): static
    {
        $this->nationality = $nationality;
        return $this;
    }

    public function getMaritalStatus(): ?string
    {
        return $this->maritalStatus;
    }

    public function setMaritalStatus(string $maritalStatus): static
    {
        $this->maritalStatus = $maritalStatus;
        return $this;
    }

    public function getDependents(): ?int
    {
        return $this->dependents;
    }

    public function setDependents(int $dependents): static
    {
        $this->dependents = $dependents;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getHousingType(): ?string
    {
        return $this->housingType;
    }

    public function setHousingType(string $housingType): static
    {
        $this->housingType = $housingType;
        return $this;
    }

    public function getMonthlyIncome(): ?string
    {
        return $this->monthlyIncome;
    }

    public function setMonthlyIncome(string $monthlyIncome): static
    {
        $this->monthlyIncome = $monthlyIncome;
        return $this;
    }

    public function getEmploymentType(): ?string
    {
        return $this->employmentType;
    }

    public function setEmploymentType(string $employmentType): static
    {
        $this->employmentType = $employmentType;
        return $this;
    }

    public function getEmployer(): ?string
    {
        return $this->employer;
    }

    public function setEmployer(?string $employer): static
    {
        $this->employer = $employer;
        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): static
    {
        $this->jobTitle = $jobTitle;
        return $this;
    }

    public function getEmploymentStartDate(): ?\DateTimeInterface
    {
        return $this->employmentStartDate;
    }

    public function setEmploymentStartDate(?\DateTimeInterface $employmentStartDate): static
    {
        $this->employmentStartDate = $employmentStartDate;
        return $this;
    }

    public function getMonthlyExpenses(): ?string
    {
        return $this->monthlyExpenses;
    }

    public function setMonthlyExpenses(string $monthlyExpenses): static
    {
        $this->monthlyExpenses = $monthlyExpenses;
        return $this;
    }

    public function getExistingLoans(): ?string
    {
        return $this->existingLoans;
    }

    public function setExistingLoans(?string $existingLoans): static
    {
        $this->existingLoans = $existingLoans;
        return $this;
    }

    public function getBankName(): ?string
    {
        return $this->bankName;
    }

    public function setBankName(string $bankName): static
    {
        $this->bankName = $bankName;
        return $this;
    }

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(?string $accountNumber): static
    {
        $this->accountNumber = $accountNumber;
        return $this;
    }

    public function getLoanAmount(): ?string
    {
        return $this->loanAmount;
    }

    public function setLoanAmount(string $loanAmount): static
    {
        $this->loanAmount = $loanAmount;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getDurationUnit(): ?DurationUnitEnum
    {
        return $this->durationUnit;
    }

    public function setDurationUnit(DurationUnitEnum $durationUnit): static
    {
        $this->durationUnit = $durationUnit;
        return $this;
    }

    public function getCreditType(): ?CreditTypeEnum
    {
        return $this->creditType;
    }

    public function setCreditType(CreditTypeEnum $creditType): static
    {
        $this->creditType = $creditType;
        return $this;
    }

    public function getMonthlyPayment(): ?string
    {
        return $this->monthlyPayment;
    }

    public function setMonthlyPayment(string $monthlyPayment): static
    {
        $this->monthlyPayment = $monthlyPayment;
        return $this;
    }

    public function getTotalCost(): ?string
    {
        return $this->totalCost;
    }

    public function setTotalCost(string $totalCost): static
    {
        $this->totalCost = $totalCost;
        return $this;
    }

    public function isTermsAccepted(): ?bool
    {
        return $this->termsAccepted;
    }

    public function setTermsAccepted(bool $termsAccepted): static
    {
        $this->termsAccepted = $termsAccepted;
        return $this;
    }

    public function isDataProcessingAccepted(): ?bool
    {
        return $this->dataProcessingAccepted;
    }

    public function setDataProcessingAccepted(bool $dataProcessingAccepted): static
    {
        $this->dataProcessingAccepted = $dataProcessingAccepted;
        return $this;
    }

    public function isMarketingAccepted(): ?bool
    {
        return $this->marketingAccepted;
    }

    public function setMarketingAccepted(bool $marketingAccepted): static
    {
        $this->marketingAccepted = $marketingAccepted;
        return $this;
    }

    public function getStatus(): CreditApplicationStatusEnum
    {
        return $this->status;
    }

    public function setStatus(CreditApplicationStatusEnum $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getAge(): ?int
    {
        if (!$this->birthDate) {
            return null;
        }
        
        return (new \DateTime())->diff($this->birthDate)->y;
    }

    public function getConfirmationHash(): ?string
    {
        return $this->confirmationHash;
    }

    public function setConfirmationHash(string $confirmationHash): static
    {
        $this->confirmationHash = $confirmationHash;
        return $this;
    }

    public function generateConfirmationHash(): static
    {
        $this->confirmationHash = md5(uniqid($this->email . time(), true));
        return $this;
    }

    public function getReferenceNumber(): ?string
    {
        return $this->referenceNumber;
    }

    public function setReferenceNumber(string $referenceNumber): static
    {
        $this->referenceNumber = $referenceNumber;
        return $this;
    }

    public function generateReferenceNumber(): static
    {
        // Générer un numéro de référence unique de 12 caractères (A-Z, 0-9)
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $reference = '';
        
        for ($i = 0; $i < 12; $i++) {
            $reference .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        $this->referenceNumber = $reference;
        return $this;
    }

    public function getContractPath(): ?string
    {
        return $this->contractPath;
    }

    public function setContractPath(?string $contractPath): static
    {
        $this->contractPath = $contractPath;
        return $this;
    }

    public function getResendCount(): ?int
    {
        return $this->resendCount;
    }

    public function setResendCount(?int $resendCount): static
    {
        $this->resendCount = $resendCount;
        return $this;
    }

    public function incrementResendCount(): static
    {
        $this->resendCount = ($this->resendCount ?? 0) + 1;
        return $this;
    }

    /**
     * @return Collection<int, AmortizationSchedule>
     */
    public function getAmortizationSchedules(): Collection
    {
        return $this->amortizationSchedules;
    }

    public function addAmortizationSchedule(AmortizationSchedule $amortizationSchedule): static
    {
        if (!$this->amortizationSchedules->contains($amortizationSchedule)) {
            $this->amortizationSchedules->add($amortizationSchedule);
            $amortizationSchedule->setCreditApplication($this);
        }

        return $this;
    }

    public function removeAmortizationSchedule(AmortizationSchedule $amortizationSchedule): static
    {
        if ($this->amortizationSchedules->removeElement($amortizationSchedule)) {
            // set the owning side to null (unless already changed)
            if ($amortizationSchedule->getCreditApplication() === $this) {
                $amortizationSchedule->setCreditApplication(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ContractFee>
     */
    public function getContractFees(): Collection
    {
        return $this->contractFees;
    }

    public function addContractFee(ContractFee $contractFee): static
    {
        if (!$this->contractFees->contains($contractFee)) {
            $this->contractFees->add($contractFee);
            $contractFee->setCreditApplication($this);
        }

        return $this;
    }

    public function removeContractFee(ContractFee $contractFee): static
    {
        if ($this->contractFees->removeElement($contractFee)) {
            // set the owning side to null (unless already changed)
            if ($contractFee->getCreditApplication() === $this) {
                $contractFee->setCreditApplication(null);
            }
        }

        return $this;
    }

    /**
     * Check if all required fees have been applied (les frais sont maintenant obligatoires)
     */
    public function hasAllRequiredFees(): bool
    {
        // Il faut au moins un frais défini pour pouvoir générer le contrat
        return $this->contractFees->count() > 0;
    }

    /**
     * Get total amount of applied fees (simplifié)
     */
    public function getTotalAppliedFees(): float
    {
        $total = 0.0;
        foreach ($this->contractFees as $fee) {
            $total += (float)$fee->getAmount();
        }
        return $total;
    }

    /**
     * Check if contract can be generated (all required fees applied)
     */
    public function canGenerateContract(): bool
    {
        return $this->hasAllRequiredFees() && $this->status === CreditApplicationStatusEnum::APPROVED;
    }

    public function __toString(): string
    {
        return sprintf('%s - %s %s (%.2f €)', 
            $this->referenceNumber ?: 'N/A',
            $this->firstName ?: '',
            $this->lastName ?: '',
            (float)($this->loanAmount ?: 0)
        );
    }

    public function getFeesStatusDisplay(): string
    {
        try {
            $count = $this->contractFees->count();
            error_log("DEBUG: CreditApplication ID={$this->id}, Fees count={$count}");
            if ($count === 0) {
                return '❌ Aucun frais';
            }
            return sprintf('✅ %d frais défini%s', $count, $count > 1 ? 's' : '');
        } catch (\Exception $e) {
            error_log("ERROR in getFeesStatusDisplay: " . $e->getMessage());
            return '⚠️ Erreur: ' . $e->getMessage();
        }
    }

    /**
     * Get the loan associated with this credit application
     */
    public function getLoan(): ?Loan
    {
        // Retourne le premier loan associé (normalement il n'y en a qu'un)
        return $this->loans->isEmpty() ? null : $this->loans->first();
    }

    /**
     * Get all loans associated with this credit application
     */
    public function getLoans(): Collection
    {
        return $this->loans;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;
        return $this;
    }
}
