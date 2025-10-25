<?php

namespace App\Entity;

use App\Repository\CardOppositionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CardOppositionRepository::class)]
#[ORM\Table(name: 'card_oppositions')]
class CardOpposition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Card::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Card $card = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $requestedBy = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(['lost', 'stolen', 'compromised', 'fraudulent_activity', 'damaged', 'other'])]
    private ?string $reason = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(['pending', 'processed', 'cancelled'])]
    private ?string $status = 'pending';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $processedBy = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $reference = null;

    #[ORM\Column]
    private ?bool $newCardRequested = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->generateReference();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getRequestedBy(): ?User
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(?User $requestedBy): static
    {
        $this->requestedBy = $requestedBy;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        if ($status === 'processed') {
            $this->processedAt = new \DateTimeImmutable();
        }
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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function isNewCardRequested(): ?bool
    {
        return $this->newCardRequested;
    }

    public function setNewCardRequested(bool $newCardRequested): static
    {
        $this->newCardRequested = $newCardRequested;
        return $this;
    }

    private function generateReference(): void
    {
        // Génération de référence aléatoire : OP + 16 caractères
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 16; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }
        $this->reference = 'OP' . $randomString;
    }

    public function process(User $admin): static
    {
        $this->setStatus('processed');
        $this->setProcessedBy($admin);
        return $this;
    }

    public function cancel(): static
    {
        $this->setStatus('cancelled');
        return $this;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }
}
