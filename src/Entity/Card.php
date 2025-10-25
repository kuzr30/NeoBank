<?php

namespace App\Entity;

use App\Repository\CardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CardRepository::class)]
#[ORM\Table(name: 'cards')]
class Card
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 19, unique: true)]
    private ?string $cardNumber = null;

    #[ORM\Column(length: 100)]
    private ?string $cardholderName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $expiryDate = null;

    #[ORM\Column(length: 4)]
    private ?string $cvv = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(['debit', 'credit', 'prepaid'])]
    private ?string $type = 'debit';

    #[ORM\Column(length: 50)]
    #[Assert\Choice(['classic', 'gold', 'platinum', 'black'])]
    private ?string $category = 'classic';

    #[ORM\Column(length: 20)]
    #[Assert\Choice(['active', 'blocked', 'expired', 'cancelled'])]
    private ?string $status = 'active';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $dailyLimit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $monthlyLimit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $creditLimit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $dailySpent = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $monthlySpent = '0.00';

    #[ORM\Column]
    private ?bool $contactless = true;

    #[ORM\Column]
    private ?bool $onlinePayments = true;

    #[ORM\Column]
    private ?bool $internationalPayments = true;

    #[ORM\Column(nullable: true)]
    private ?int $pinAttempts = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $blockedAt = null;

    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'cards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $account = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $settings = [];

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->generateCardNumber();
        $this->generateCvv();
        $this->generateExpiryDate();
        $this->initializeDefaults();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCardNumber(): ?string
    {
        return $this->cardNumber;
    }

    public function setCardNumber(string $cardNumber): static
    {
        $this->cardNumber = $cardNumber;
        return $this;
    }

    private function generateCardNumber(): void
    {
        // Génère un numéro de carte factice pour la démo
        $this->cardNumber = '4000 ' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT) . 
                           ' ' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT) . 
                           ' ' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function getCardholderName(): ?string
    {
        return $this->cardholderName;
    }

    public function setCardholderName(string $cardholderName): static
    {
        $this->cardholderName = strtoupper($cardholderName);
        return $this;
    }

    public function getExpiryDate(): ?\DateTimeInterface
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(\DateTimeInterface $expiryDate): static
    {
        $this->expiryDate = $expiryDate;
        return $this;
    }

    private function generateExpiryDate(): void
    {
        // Expire dans 3 ans
        $this->expiryDate = new \DateTime('+3 years');
    }

    public function getCvv(): ?string
    {
        return $this->cvv;
    }

    public function setCvv(string $cvv): static
    {
        $this->cvv = $cvv;
        return $this;
    }

    private function generateCvv(): void
    {
        $this->cvv = str_pad(random_int(1, 999), 3, '0', STR_PAD_LEFT);
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
        if ($status === 'blocked') {
            $this->blockedAt = new \DateTimeImmutable();
        }
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDailyLimit(): ?string
    {
        return $this->dailyLimit;
    }

    public function setDailyLimit(?string $dailyLimit): static
    {
        $this->dailyLimit = $dailyLimit;
        return $this;
    }

    public function getMonthlyLimit(): ?string
    {
        return $this->monthlyLimit;
    }

    public function setMonthlyLimit(?string $monthlyLimit): static
    {
        $this->monthlyLimit = $monthlyLimit;
        return $this;
    }

    public function getCreditLimit(): ?string
    {
        return $this->creditLimit;
    }

    public function setCreditLimit(?string $creditLimit): static
    {
        $this->creditLimit = $creditLimit;
        return $this;
    }

    public function getDailySpent(): ?string
    {
        return $this->dailySpent;
    }

    public function setDailySpent(string $dailySpent): static
    {
        $this->dailySpent = $dailySpent;
        return $this;
    }

    public function getMonthlySpent(): ?string
    {
        return $this->monthlySpent;
    }

    public function setMonthlySpent(string $monthlySpent): static
    {
        $this->monthlySpent = $monthlySpent;
        return $this;
    }

    public function isContactless(): ?bool
    {
        return $this->contactless;
    }

    public function setContactless(bool $contactless): static
    {
        $this->contactless = $contactless;
        return $this;
    }

    public function isOnlinePayments(): ?bool
    {
        return $this->onlinePayments;
    }

    public function setOnlinePayments(bool $onlinePayments): static
    {
        $this->onlinePayments = $onlinePayments;
        return $this;
    }

    public function isInternationalPayments(): ?bool
    {
        return $this->internationalPayments;
    }

    public function setInternationalPayments(bool $internationalPayments): static
    {
        $this->internationalPayments = $internationalPayments;
        return $this;
    }

    public function getPinAttempts(): ?int
    {
        return $this->pinAttempts;
    }

    public function setPinAttempts(?int $pinAttempts): static
    {
        $this->pinAttempts = $pinAttempts;
        return $this;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeImmutable $lastUsedAt): static
    {
        $this->lastUsedAt = $lastUsedAt;
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

    public function getBlockedAt(): ?\DateTimeImmutable
    {
        return $this->blockedAt;
    }

    public function setBlockedAt(?\DateTimeImmutable $blockedAt): static
    {
        $this->blockedAt = $blockedAt;
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

    public function getSettings(): ?array
    {
        return $this->settings;
    }

    public function setSettings(?array $settings): static
    {
        $this->settings = $settings;
        return $this;
    }

    private function initializeDefaults(): void
    {
        switch ($this->category) {
            case 'classic':
                $this->dailyLimit = '300.00';
                $this->monthlyLimit = '3000.00';
                break;
            case 'gold':
                $this->dailyLimit = '500.00';
                $this->monthlyLimit = '5000.00';
                break;
            case 'platinum':
                $this->dailyLimit = '1000.00';
                $this->monthlyLimit = '10000.00';
                break;
            case 'black':
                $this->dailyLimit = '2000.00';
                $this->monthlyLimit = '20000.00';
                break;
        }
    }

    public function getMaskedCardNumber(): string
    {
        if (!$this->cardNumber) {
            return '';
        }
        
        $cleaned = str_replace(' ', '', $this->cardNumber);
        return '**** **** **** ' . substr($cleaned, -4);
    }

    public function isExpired(): bool
    {
        return $this->expiryDate < new \DateTime();
    }

    public function canMakePayment(string $amount): bool
    {
        if ($this->status !== 'active' || $this->isExpired()) {
            return false;
        }

        $dailyRemaining = (float) $this->dailyLimit - (float) $this->dailySpent;
        $monthlyRemaining = (float) $this->monthlyLimit - (float) $this->monthlySpent;

        return (float) $amount <= $dailyRemaining && (float) $amount <= $monthlyRemaining;
    }

    public function recordPayment(string $amount): void
    {
        $this->dailySpent = (string) ((float) $this->dailySpent + (float) $amount);
        $this->monthlySpent = (string) ((float) $this->monthlySpent + (float) $amount);
        $this->lastUsedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }
}
