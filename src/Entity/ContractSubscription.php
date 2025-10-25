<?php

namespace App\Entity;

use App\Repository\ContractSubscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContractSubscriptionRepository::class)]
#[ORM\Table(name: 'contract_subscriptions')]
class ContractSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: CardSubscription::class, inversedBy: 'contract')]
    #[ORM\JoinColumn(name: 'card_subscription_id', referencedColumnName: 'id', nullable: false)]
    private ?CardSubscription $cardSubscription = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(['pending', 'sent', 'signed', 'expired'])]
    private string $status = 'pending';

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    private ?string $cardFees = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $dailyLimit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $monthlyLimit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $creditLimit = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $generalConditions = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $specificConditions = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contractPdfPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $signedContractPdfPath = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $signedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $signatureData = null; // Données de signature électronique

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $signerIp = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $signerUserAgent = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        // Référence plus courte : CT + timestamp + 4 chars aléatoires
        $randomChars = substr(str_shuffle('0123456789ABCDEF'), 0, 4);
        $this->reference = 'CT' . time() . $randomChars;
        // Contrat expire après 30 jours si pas signé
        $this->expiresAt = (new \DateTimeImmutable())->modify('+30 days');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCardSubscription(): ?CardSubscription
    {
        return $this->cardSubscription;
    }

    public function setCardSubscription(?CardSubscription $cardSubscription): static
    {
        $this->cardSubscription = $cardSubscription;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        
        // Mise à jour automatique des dates selon le statut
        if ($status === 'sent' && !$this->sentAt) {
            $this->sentAt = new \DateTimeImmutable();
        } elseif ($status === 'signed' && !$this->signedAt) {
            $this->signedAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getCardFees(): ?string
    {
        return $this->cardFees;
    }

    public function setCardFees(string $cardFees): static
    {
        $this->cardFees = $cardFees;
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

    public function getGeneralConditions(): ?string
    {
        return $this->generalConditions;
    }

    public function setGeneralConditions(?string $generalConditions): static
    {
        $this->generalConditions = $generalConditions;
        return $this;
    }

    public function getSpecificConditions(): ?string
    {
        return $this->specificConditions;
    }

    public function setSpecificConditions(?string $specificConditions): static
    {
        $this->specificConditions = $specificConditions;
        return $this;
    }

    public function getContractPdfPath(): ?string
    {
        return $this->contractPdfPath;
    }

    public function setContractPdfPath(?string $contractPdfPath): static
    {
        $this->contractPdfPath = $contractPdfPath;
        return $this;
    }

    public function getSignedContractPdfPath(): ?string
    {
        return $this->signedContractPdfPath;
    }

    public function setSignedContractPdfPath(?string $signedContractPdfPath): static
    {
        $this->signedContractPdfPath = $signedContractPdfPath;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function getSignedAt(): ?\DateTimeImmutable
    {
        return $this->signedAt;
    }

    public function setSignedAt(?\DateTimeImmutable $signedAt): static
    {
        $this->signedAt = $signedAt;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
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

    public function getSignatureData(): ?string
    {
        return $this->signatureData;
    }

    public function setSignatureData(?string $signatureData): static
    {
        $this->signatureData = $signatureData;
        return $this;
    }

    public function getSignerIp(): ?string
    {
        return $this->signerIp;
    }

    public function setSignerIp(?string $signerIp): static
    {
        $this->signerIp = $signerIp;
        return $this;
    }

    public function getSignerUserAgent(): ?string
    {
        return $this->signerUserAgent;
    }

    public function setSignerUserAgent(?string $signerUserAgent): static
    {
        $this->signerUserAgent = $signerUserAgent;
        return $this;
    }

    // Méthodes utilitaires
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isSigned(): bool
    {
        return $this->status === 'signed';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->expiresAt && $this->expiresAt < new \DateTimeImmutable());
    }

    public function canBeSigned(): bool
    {
        return $this->isSent() && !$this->isExpired() && !$this->isSigned();
    }

    public function getCardTypeDisplayName(): string
    {
        if (!$this->cardSubscription) {
            return 'Inconnue';
        }

        return match($this->cardSubscription->getCardType()) {
            'classic' => 'Classic',
            'gold' => 'Gold',
            'platinum' => 'Platinum',
            default => ucfirst($this->cardSubscription->getCardType())
        };
    }

    public function getCardBrandDisplayName(): string
    {
        if (!$this->cardSubscription) {
            return 'Inconnue';
        }

        return match($this->cardSubscription->getCardBrand()) {
            'visa' => 'Visa',
            'mastercard' => 'Mastercard',
            default => ucfirst($this->cardSubscription->getCardBrand())
        };
    }
}
