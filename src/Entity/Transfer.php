<?php

namespace App\Entity;

use App\Repository\TransferRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TransferRepository::class)]
#[ORM\Table(name: 'transfers')]
class Transfer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: BankAccount::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?BankAccount $destinationAccount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\GreaterThan(0)]
    private ?string $amount = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(['pending', 'executing', 'completed', 'expired', 'cancelled', 'blocked'])]
    private ?string $status = 'pending';

    #[ORM\Column]
    private ?int $currentCodeIndex = 1;

    #[ORM\Column]
    private ?int $failedAttemptsTotal = 0;

    #[ORM\Column]
    private bool $isAccountBlocked = false;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $executedAt = null;

    #[ORM\OneToMany(mappedBy: 'transfer', targetEntity: TransferCode::class, cascade: ['persist', 'remove'])]
    private Collection $transferCodes;

    #[ORM\OneToMany(mappedBy: 'transfer', targetEntity: TransferAttempt::class, cascade: ['persist', 'remove'])]
    private Collection $transferAttempts;

    public function __construct()
    {
        $this->transferCodes = new ArrayCollection();
        $this->transferAttempts = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->generateReference();
        $this->setExpiresAt();
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

    public function getDestinationAccount(): ?BankAccount
    {
        return $this->destinationAccount;
    }

    public function setDestinationAccount(?BankAccount $destinationAccount): static
    {
        $this->destinationAccount = $destinationAccount;
        return $this;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCurrentCodeIndex(): ?int
    {
        return $this->currentCodeIndex;
    }

    public function setCurrentCodeIndex(int $currentCodeIndex): static
    {
        $this->currentCodeIndex = $currentCodeIndex;
        return $this;
    }

    public function getFailedAttemptsTotal(): ?int
    {
        return $this->failedAttemptsTotal;
    }

    public function setFailedAttemptsTotal(int $failedAttemptsTotal): static
    {
        $this->failedAttemptsTotal = $failedAttemptsTotal;
        return $this;
    }

    public function incrementFailedAttempts(): static
    {
        $this->failedAttemptsTotal++;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isAccountBlocked(): bool
    {
        return $this->isAccountBlocked;
    }

    public function setIsAccountBlocked(bool $isAccountBlocked): static
    {
        $this->isAccountBlocked = $isAccountBlocked;
        if ($isAccountBlocked) {
            $this->setStatus('blocked');
        }
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
        // Génération complètement aléatoire : 20 caractères alphanumériques
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 20; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }
        $this->reference = $randomString;
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

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(): static
    {
        // Le virement expire 24H après création
        $this->expiresAt = new \DateTimeImmutable('+24 hours');
        return $this;
    }

    public function getExecutedAt(): ?\DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function setExecutedAt(?\DateTimeImmutable $executedAt): static
    {
        $this->executedAt = $executedAt;
        return $this;
    }

    /**
     * @return Collection<int, TransferCode>
     */
    public function getTransferCodes(): Collection
    {
        return $this->transferCodes;
    }

    public function addTransferCode(TransferCode $transferCode): static
    {
        if (!$this->transferCodes->contains($transferCode)) {
            $this->transferCodes->add($transferCode);
            $transferCode->setTransfer($this);
        }
        return $this;
    }

    public function removeTransferCode(TransferCode $transferCode): static
    {
        if ($this->transferCodes->removeElement($transferCode)) {
            if ($transferCode->getTransfer() === $this) {
                $transferCode->setTransfer(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, TransferAttempt>
     */
    public function getTransferAttempts(): Collection
    {
        return $this->transferAttempts;
    }

    public function addTransferAttempt(TransferAttempt $transferAttempt): static
    {
        if (!$this->transferAttempts->contains($transferAttempt)) {
            $this->transferAttempts->add($transferAttempt);
            $transferAttempt->setTransfer($this);
        }
        return $this;
    }

    public function removeTransferAttempt(TransferAttempt $transferAttempt): static
    {
        if ($this->transferAttempts->removeElement($transferAttempt)) {
            if ($transferAttempt->getTransfer() === $this) {
                $transferAttempt->setTransfer(null);
            }
        }
        return $this;
    }

    /**
     * Vérifie si le virement a expiré
     */
    public function isExpired(): bool
    {
        return $this->expiresAt && $this->expiresAt < new \DateTimeImmutable();
    }

    /**
     * Obtient le code actuel à valider
     */
    public function getCurrentCode(): ?TransferCode
    {
        foreach ($this->transferCodes as $code) {
            if ($code->getCodeOrder() === $this->currentCodeIndex && $code->getStatus() === 'pending') {
                return $code;
            }
        }
        return null;
    }

    /**
     * Vérifie si tous les codes ont été validés
     */
    public function isFullyValidated(): bool
    {
        foreach ($this->transferCodes as $code) {
            if ($code->getStatus() !== 'validated') {
                return false;
            }
        }
        return $this->transferCodes->count() > 0;
    }
}
