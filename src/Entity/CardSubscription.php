<?php

namespace App\Entity;

use App\Repository\CardSubscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CardSubscriptionRepository::class)]
#[ORM\Table(name: 'card_subscriptions')]
class CardSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $account = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(['mastercard', 'visa'])]
    private ?string $cardBrand = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(['classic', 'gold', 'platinum'])]
    private ?string $cardType = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(['pending', 'fees_set', 'approved', 'signed', 'payment_pending', 'active', 'rejected', 'cancelled'])]
    private ?string $status = 'pending';

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $reason = null; // Raison du rejet ou commentaire admin

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $processedBy = null; // Admin qui a traité la demande

    #[ORM\OneToOne(targetEntity: Card::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'card_id', referencedColumnName: 'id', nullable: true)]
    private ?Card $card = null; // Carte créée après approbation

    #[ORM\OneToOne(targetEntity: ContractSubscription::class, mappedBy: 'cardSubscription', cascade: ['persist', 'remove'])]
    private ?ContractSubscription $contract = null; // Contrat de souscription

    #[ORM\Column(length: 50, unique: true)]
    private ?string $reference = null;

    // Frais définis par l'admin
    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $activationFee = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $monthlyFee = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $dailyLimit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $monthlyLimit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $creditLimit = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->generateReference();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
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

    public function getCardBrand(): ?string
    {
        return $this->cardBrand;
    }

    public function setCardBrand(string $cardBrand): static
    {
        $this->cardBrand = $cardBrand;
        return $this;
    }

    public function getCardType(): ?string
    {
        return $this->cardType;
    }

    public function setCardType(string $cardType): static
    {
        $this->cardType = $cardType;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        if (in_array($status, ['approved', 'rejected'])) {
            $this->processedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function getProcessedBy(): ?User
    {
        return $this->processedBy;
    }

    public function setProcessedBy(?User $processedBy): static
    {
        $this->processedBy = $processedBy;
        return $this;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): static
    {
        $this->processedAt = $processedAt;
        return $this;
    }

    public function getCard(): ?Card
    {
        return $this->card;
    }

    public function setCard(?Card $card): static
    {
        $this->card = $card;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    private function generateReference(): void
    {
        // Génération de référence aléatoire : CS + 16 caractères
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 16; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }
        $this->reference = 'CS' . $randomString;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function approve(User $admin): static
    {
        $this->setStatus('approved');
        $this->setProcessedBy($admin);
        $this->setProcessedAt(new \DateTimeImmutable());
        return $this;
    }

    public function reject(User $admin, string $reason): static
    {
        $this->setStatus('rejected');
        $this->setProcessedBy($admin);
        $this->setProcessedAt(new \DateTimeImmutable());
        $this->setReason($reason);
        return $this;
    }

    public function getContract(): ?ContractSubscription
    {
        return $this->contract;
    }

    public function setContract(?ContractSubscription $contract): static
    {
        $this->contract = $contract;
        return $this;
    }

    public function hasSignedContract(): bool
    {
        return $this->contract && $this->contract->isSigned();
    }

    public function hasExpiredContract(): bool
    {
        return $this->contract && $this->contract->isExpired();
    }

    // Getters et Setters pour les frais
    public function getActivationFee(): ?string
    {
        return $this->activationFee;
    }

    public function setActivationFee(?string $activationFee): static
    {
        $this->activationFee = $activationFee;
        return $this;
    }

    public function getMonthlyFee(): ?string
    {
        return $this->monthlyFee;
    }

    public function setMonthlyFee(?string $monthlyFee): static
    {
        $this->monthlyFee = $monthlyFee;
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

    public function hasFeesSet(): bool
    {
        return $this->status === 'fees_set';
    }

    public function setFeesAndUpdate(
        ?string $activationFee = null,
        ?string $monthlyFee = null,
        ?string $dailyLimit = null,
        ?string $monthlyLimit = null,
        ?string $creditLimit = null
    ): static {
        $this->setActivationFee($activationFee);
        $this->setMonthlyFee($monthlyFee);
        $this->setDailyLimit($dailyLimit);
        $this->setMonthlyLimit($monthlyLimit);
        $this->setCreditLimit($creditLimit);
        $this->setStatus('fees_set');
        return $this;
    }
}
