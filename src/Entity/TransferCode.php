<?php

namespace App\Entity;

use App\Repository\TransferCodeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TransferCodeRepository::class)]
#[ORM\Table(name: 'transfer_codes')]
class TransferCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Transfer::class, inversedBy: 'transferCodes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Transfer $transfer = null;

    #[ORM\Column]
    private ?int $codeOrder = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $codeName = null;

    #[ORM\Column(length: 9)]
    #[Assert\NotBlank]
    #[Assert\Length(exactly: 9)]
    #[Assert\Regex(pattern: '/^[A-Z0-9]{9}$/', message: 'Le code doit contenir exactement 9 caractères alphanumériques')]
    private ?string $codeValue = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(['pending', 'validated', 'expired'])]
    private ?string $status = 'pending';

    #[ORM\Column]
    private ?int $failedAttempts = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\OneToMany(mappedBy: 'transferCode', targetEntity: TransferAttempt::class, cascade: ['persist', 'remove'])]
    private Collection $transferAttempts;

    public function __construct()
    {
        $this->transferAttempts = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransfer(): ?Transfer
    {
        return $this->transfer;
    }

    public function setTransfer(?Transfer $transfer): static
    {
        $this->transfer = $transfer;
        return $this;
    }

    public function getCodeOrder(): ?int
    {
        return $this->codeOrder;
    }

    public function setCodeOrder(int $codeOrder): static
    {
        $this->codeOrder = $codeOrder;
        return $this;
    }

    public function getCodeName(): ?string
    {
        return $this->codeName;
    }

    public function setCodeName(string $codeName): static
    {
        $this->codeName = $codeName;
        return $this;
    }

    public function getCodeValue(): ?string
    {
        return $this->codeValue;
    }

    public function setCodeValue(string $codeValue): static
    {
        // Convertir en majuscules et nettoyer
        $this->codeValue = strtoupper(preg_replace('/[^A-Z0-9]/', '', $codeValue));
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getFailedAttempts(): ?int
    {
        return $this->failedAttempts;
    }

    public function setFailedAttempts(int $failedAttempts): static
    {
        $this->failedAttempts = $failedAttempts;
        return $this;
    }

    public function incrementFailedAttempts(): static
    {
        $this->failedAttempts++;
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

    public function getValidatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeImmutable $validatedAt): static
    {
        $this->validatedAt = $validatedAt;
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
            $transferAttempt->setTransferCode($this);
        }
        return $this;
    }

    public function removeTransferAttempt(TransferAttempt $transferAttempt): static
    {
        if ($this->transferAttempts->removeElement($transferAttempt)) {
            if ($transferAttempt->getTransferCode() === $this) {
                $transferAttempt->setTransferCode(null);
            }
        }
        return $this;
    }

    /**
     * Valide le code et définit son expiration à 6H
     */
    public function validate(): static
    {
        $this->status = 'validated';
        $this->validatedAt = new \DateTimeImmutable();
        $this->expiresAt = new \DateTimeImmutable('+6 hours');
        return $this;
    }

    /**
     * Vérifie si le code a expiré (6H après validation)
     */
    public function isExpired(): bool
    {
        return $this->expiresAt && $this->expiresAt < new \DateTimeImmutable();
    }

    /**
     * Vérifie si le code est valide pour validation
     */
    public function canBeValidated(): bool
    {
        return $this->status === 'pending' && $this->failedAttempts < 3;
    }

    /**
     * Vérifie si le code a été validé
     */
    public function isValidated(): bool
    {
        return $this->status === 'validated';
    }

    /**
     * Génère un code aléatoire de 9 caractères
     */
    public static function generateRandomCode(): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < 9; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $code;
    }
}
