<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: 'transactions')]
#[ORM\Index(columns: ['created_at'], name: 'idx_transaction_created_at')]
#[ORM\Index(columns: ['type'], name: 'idx_transaction_type')]
#[ORM\Index(columns: ['status'], name: 'idx_transaction_status')]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\NotBlank()]
    #[Assert\Positive()]
    private ?string $amount = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(['debit', 'credit'])]
    private ?string $type = null;

    #[ORM\Column(length: 100)]
    #[Assert\Choice([
        'transfer', 'deposit', 'withdrawal', 'payment', 'fee', 
        'interest', 'loan_payment', 'card_payment', 'direct_debit',
        'check', 'cash', 'online_payment', 'refund'
    ])]
    private ?string $category = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(['pending', 'completed', 'failed', 'cancelled'])]
    private ?string $status = 'pending';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $balanceAfter = null;

    #[ORM\Column(length: 3)]
    private ?string $currency = 'EUR';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $account = null;

    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Account $destinationAccount = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $initiatedBy = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = [];

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->generateReference();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    private function generateReference(): void
    {
        $this->reference = 'TXN' . date('Ymd') . strtoupper(substr(uniqid(), -8));
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
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

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        if ($status === 'completed' && !$this->processedAt) {
            $this->processedAt = new \DateTimeImmutable();
        }
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
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

    public function getBalanceAfter(): ?string
    {
        return $this->balanceAfter;
    }

    public function setBalanceAfter(?string $balanceAfter): static
    {
        $this->balanceAfter = $balanceAfter;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): static
    {
        $this->processedAt = $processedAt;
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

    public function getDestinationAccount(): ?Account
    {
        return $this->destinationAccount;
    }

    public function setDestinationAccount(?Account $destinationAccount): static
    {
        $this->destinationAccount = $destinationAccount;
        return $this;
    }

    public function getInitiatedBy(): ?User
    {
        return $this->initiatedBy;
    }

    public function setInitiatedBy(?User $initiatedBy): static
    {
        $this->initiatedBy = $initiatedBy;
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

    public function addMetadata(string $key, mixed $value): static
    {
        if (!$this->metadata) {
            $this->metadata = [];
        }
        $this->metadata[$key] = $value;
        return $this;
    }

    public function isTransfer(): bool
    {
        return $this->category === 'transfer' && $this->destinationAccount !== null;
    }

    public function isInternal(): bool
    {
        return $this->destinationAccount !== null;
    }

    public function getDisplayAmount(): string
    {
        $amount = (float) $this->amount;
        return ($this->type === 'debit' ? '-' : '+') . number_format($amount, 2, ',', ' ') . ' ' . $this->currency;
    }
}
