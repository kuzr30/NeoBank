<?php

namespace App\Entity;

use App\Repository\LoanPaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LoanPaymentRepository::class)]
#[ORM\Table(name: 'loan_payments')]
#[ORM\Index(columns: ['due_date'], name: 'idx_loan_payment_due_date')]
#[ORM\Index(columns: ['status'], name: 'idx_loan_payment_status')]
class LoanPayment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $paymentNumber = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\NotBlank()]
    #[Assert\Positive()]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $principalAmount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $interestAmount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $feeAmount = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(['regular', 'partial', 'extra', 'late_fee', 'penalty'])]
    private ?string $type = 'regular';

    #[ORM\Column(length: 20)]
    #[Assert\Choice(['scheduled', 'pending', 'completed', 'failed', 'cancelled'])]
    private ?string $status = 'scheduled';

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Loan::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Loan $loan = null;

    #[ORM\ManyToOne(targetEntity: Transaction::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Transaction $transaction = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = [];

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->generatePaymentNumber();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPaymentNumber(): ?string
    {
        return $this->paymentNumber;
    }

    public function setPaymentNumber(string $paymentNumber): static
    {
        $this->paymentNumber = $paymentNumber;
        return $this;
    }

    private function generatePaymentNumber(): void
    {
        $this->paymentNumber = 'PMT' . date('Ymd') . strtoupper(substr(uniqid(), -6));
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        $this->calculateBreakdown();
        return $this;
    }

    public function getPrincipalAmount(): ?string
    {
        return $this->principalAmount;
    }

    public function setPrincipalAmount(string $principalAmount): static
    {
        $this->principalAmount = $principalAmount;
        return $this;
    }

    public function getInterestAmount(): ?string
    {
        return $this->interestAmount;
    }

    public function setInterestAmount(string $interestAmount): static
    {
        $this->interestAmount = $interestAmount;
        return $this;
    }

    public function getFeeAmount(): ?string
    {
        return $this->feeAmount;
    }

    public function setFeeAmount(?string $feeAmount): static
    {
        $this->feeAmount = $feeAmount;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
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
        
        if ($status === 'completed' && !$this->paidAt) {
            $this->paidAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;
        return $this;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTimeImmutable $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;
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

    public function getLoan(): ?Loan
    {
        return $this->loan;
    }

    public function setLoan(?Loan $loan): static
    {
        $this->loan = $loan;
        return $this;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(?Transaction $transaction): static
    {
        $this->transaction = $transaction;
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

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    private function calculateBreakdown(): void
    {
        if (!$this->loan || !$this->amount) {
            return;
        }

        $remainingBalance = (float) $this->loan->getRemainingAmount();
        $monthlyRate = (float) $this->loan->getInterestRate() / 100 / 12;
        $paymentAmount = (float) $this->amount;

        // Calcul de la part d'intérêts
        $interestPortion = $remainingBalance * $monthlyRate;
        $this->interestAmount = (string) round($interestPortion, 2);

        // Calcul de la part du capital
        $principalPortion = $paymentAmount - $interestPortion;
        $this->principalAmount = (string) round(max(0, $principalPortion), 2);
    }

    public function isOverdue(): bool
    {
        if (!$this->dueDate || $this->status === 'completed') {
            return false;
        }

        return new \DateTime() > $this->dueDate;
    }

    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        $now = new \DateTime();
        return $now->diff($this->dueDate)->days;
    }

    public function isLate(): bool
    {
        return $this->isOverdue() && $this->status !== 'completed';
    }

    public function getLateFee(): string
    {
        if (!$this->isLate()) {
            return '0.00';
        }

        $daysLate = $this->getDaysOverdue();
        $baseFee = 25.00; // Frais de base
        $dailyFee = 2.00; // Frais par jour de retard

        return (string) ($baseFee + ($daysLate * $dailyFee));
    }

    public function getTotalAmountDue(): string
    {
        $amount = (float) $this->amount;
        $lateFee = (float) $this->getLateFee();
        $fee = $this->feeAmount ? (float) $this->feeAmount : 0;

        return (string) ($amount + $lateFee + $fee);
    }
}
