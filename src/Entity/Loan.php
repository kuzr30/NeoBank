<?php

namespace App\Entity;

use App\Enum\CreditTypeEnum;
use App\Repository\LoanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LoanRepository::class)]
#[ORM\Table(name: 'loans')]
class Loan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $loanNumber = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\NotBlank()]
    #[Assert\Positive()]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $remainingAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\NotBlank()]
    #[Assert\Positive()]
    private ?string $monthlyPayment = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 4)]
    #[Assert\NotBlank()]
    #[Assert\Range(min: 0, max: 100)]
    private ?string $interestRate = null;

    #[ORM\Column]
    #[Assert\NotBlank()]
    #[Assert\Positive()]
    private ?int $termMonths = null;

    #[ORM\Column]
    private ?int $remainingMonths = null;

    #[ORM\Column(length: 50, enumType: CreditTypeEnum::class)]
    #[Assert\NotNull()]
    private ?CreditTypeEnum $type = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(['pending', 'active', 'completed', 'defaulted', 'cancelled'])]
    private ?string $status = 'pending';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $purpose = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $disbursedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $firstPaymentDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $nextPaymentDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastPaymentDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $account = null;

    #[ORM\ManyToOne(targetEntity: CreditApplication::class, inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: true)]
    private ?CreditApplication $creditApplication = null;

    #[ORM\OneToMany(mappedBy: 'loan', targetEntity: LoanPayment::class, cascade: ['persist', 'remove'])]
    private Collection $payments;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $terms = [];

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $totalInterest = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $totalPaid = '0.00';

    #[ORM\Column]
    private ?int $missedPayments = 0;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->generateLoanNumber();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLoanNumber(): ?string
    {
        return $this->loanNumber;
    }

    public function setLoanNumber(string $loanNumber): static
    {
        $this->loanNumber = $loanNumber;
        return $this;
    }

    private function generateLoanNumber(): void
    {
        $this->loanNumber = 'LN' . date('Y') . str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        if (!$this->remainingAmount) {
            $this->remainingAmount = $amount;
        }
        return $this;
    }

    public function getRemainingAmount(): ?string
    {
        return $this->remainingAmount;
    }

    public function setRemainingAmount(string $remainingAmount): static
    {
        $this->remainingAmount = $remainingAmount;
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

    public function getInterestRate(): ?string
    {
        return $this->interestRate;
    }

    public function setInterestRate(string $interestRate): static
    {
        $this->interestRate = $interestRate;
        return $this;
    }

    public function getTermMonths(): ?int
    {
        return $this->termMonths;
    }

    public function setTermMonths(int $termMonths): static
    {
        $this->termMonths = $termMonths;
        if (!$this->remainingMonths) {
            $this->remainingMonths = $termMonths;
        }
        return $this;
    }

    public function getRemainingMonths(): ?int
    {
        return $this->remainingMonths;
    }

    public function setRemainingMonths(int $remainingMonths): static
    {
        $this->remainingMonths = $remainingMonths;
        return $this;
    }

    public function getType(): ?CreditTypeEnum
    {
        return $this->type;
    }

    public function setType(CreditTypeEnum $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        
        if ($status === 'approved' && !$this->approvedAt) {
            $this->approvedAt = new \DateTimeImmutable();
        } elseif ($status === 'completed' && !$this->completedAt) {
            $this->completedAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getPurpose(): ?string
    {
        return $this->purpose;
    }

    public function setPurpose(?string $purpose): static
    {
        $this->purpose = $purpose;
        return $this;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?\DateTimeImmutable $approvedAt): static
    {
        $this->approvedAt = $approvedAt;
        return $this;
    }

    public function getDisbursedAt(): ?\DateTimeImmutable
    {
        return $this->disbursedAt;
    }

    public function setDisbursedAt(?\DateTimeImmutable $disbursedAt): static
    {
        $this->disbursedAt = $disbursedAt;
        if ($disbursedAt && !$this->firstPaymentDate) {
            $this->firstPaymentDate = $disbursedAt->modify('+1 month');
            $this->nextPaymentDate = $this->firstPaymentDate;
        }
        return $this;
    }

    public function getFirstPaymentDate(): ?\DateTimeImmutable
    {
        return $this->firstPaymentDate;
    }

    public function setFirstPaymentDate(?\DateTimeImmutable $firstPaymentDate): static
    {
        $this->firstPaymentDate = $firstPaymentDate;
        return $this;
    }

    public function getNextPaymentDate(): ?\DateTimeImmutable
    {
        return $this->nextPaymentDate;
    }

    public function setNextPaymentDate(?\DateTimeImmutable $nextPaymentDate): static
    {
        $this->nextPaymentDate = $nextPaymentDate;
        return $this;
    }

    public function getLastPaymentDate(): ?\DateTimeImmutable
    {
        return $this->lastPaymentDate;
    }

    public function setLastPaymentDate(?\DateTimeImmutable $lastPaymentDate): static
    {
        $this->lastPaymentDate = $lastPaymentDate;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;
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

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): static
    {
        $this->account = $account;
        return $this;
    }

    public function getCreditApplication(): ?CreditApplication
    {
        return $this->creditApplication;
    }

    public function setCreditApplication(?CreditApplication $creditApplication): static
    {
        $this->creditApplication = $creditApplication;
        return $this;
    }

    /**
     * @return Collection<int, LoanPayment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(LoanPayment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setLoan($this);
        }
        return $this;
    }

    public function removePayment(LoanPayment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            if ($payment->getLoan() === $this) {
                $payment->setLoan(null);
            }
        }
        return $this;
    }

    public function getTerms(): ?array
    {
        return $this->terms;
    }

    public function setTerms(?array $terms): static
    {
        $this->terms = $terms;
        return $this;
    }

    public function getTotalInterest(): ?string
    {
        return $this->totalInterest;
    }

    public function setTotalInterest(string $totalInterest): static
    {
        $this->totalInterest = $totalInterest;
        return $this;
    }

    public function getTotalPaid(): ?string
    {
        return $this->totalPaid;
    }

    public function setTotalPaid(string $totalPaid): static
    {
        $this->totalPaid = $totalPaid;
        return $this;
    }

    public function getMissedPayments(): ?int
    {
        return $this->missedPayments;
    }

    public function setMissedPayments(int $missedPayments): static
    {
        $this->missedPayments = $missedPayments;
        return $this;
    }

    public function getProgressPercentage(): float
    {
        if (!$this->amount || (float) $this->amount === 0) {
            return 0;
        }
        
        $paid = (float) $this->totalPaid;
        $total = (float) $this->amount;
        
        return min(100, ($paid / $total) * 100);
    }

    public function getRemainingPercentage(): float
    {
        return 100 - $this->getProgressPercentage();
    }

    public function isOverdue(): bool
    {
        if (!$this->nextPaymentDate || $this->status !== 'active') {
            return false;
        }
        
        return $this->nextPaymentDate < new \DateTimeImmutable();
    }

    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        
        $now = new \DateTimeImmutable();
        return $now->diff($this->nextPaymentDate)->days;
    }

    public function processPayment(string $amount, string $type = 'regular'): LoanPayment
    {
        $payment = new LoanPayment();
        $payment->setLoan($this);
        $payment->setAmount($amount);
        $payment->setType($type);
        $payment->setStatus('completed');
        $payment->setPaidAt(new \DateTimeImmutable());
        
        // Mise à jour du prêt
        $this->totalPaid = (string) ((float) $this->totalPaid + (float) $amount);
        $this->remainingAmount = (string) ((float) $this->remainingAmount - (float) $amount);
        $this->lastPaymentDate = new \DateTimeImmutable();
        
        // Calcul de la prochaine échéance
        if ($this->nextPaymentDate) {
            $this->nextPaymentDate = $this->nextPaymentDate->modify('+1 month');
        }
        
        $this->remainingMonths = max(0, $this->remainingMonths - 1);
        
        // Vérification si le prêt est terminé
        if ((float) $this->remainingAmount <= 0 || $this->remainingMonths <= 0) {
            $this->status = 'completed';
            $this->completedAt = new \DateTimeImmutable();
            $this->nextPaymentDate = null;
        }
        
        $this->updatedAt = new \DateTimeImmutable();
        
        return $payment;
    }
}
