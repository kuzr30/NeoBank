<?php

namespace App\Entity;

use App\Repository\BankAccountRepository;
use App\Validator as AppValidator;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BankAccountRepository::class)]
#[ORM\Table(name: 'bank_accounts')]
class BankAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'L\'intitulé du compte est obligatoire')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'L\'intitulé doit faire au moins 3 caractères', maxMessage: 'L\'intitulé ne peut pas dépasser 255 caractères')]
    private ?string $accountName = null;

    #[ORM\Column(length: 34)]
    #[Assert\NotBlank(message: 'L\'IBAN est obligatoire')]
    #[Assert\Length(max: 34, maxMessage: 'L\'IBAN ne peut pas dépasser 34 caractères')]
    #[AppValidator\EuropeanIban]
    private ?string $iban = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de la banque est obligatoire')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le nom de la banque doit faire au moins 2 caractères', maxMessage: 'Le nom de la banque ne peut pas dépasser 255 caractères')]
    private ?string $bankName = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column]
    private bool $isActive = true;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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

    public function getAccountName(): ?string
    {
        return $this->accountName;
    }

    public function setAccountName(string $accountName): static
    {
        $this->accountName = $accountName;
        return $this;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(string $iban): static
    {
        // Nettoyer l'IBAN (enlever espaces et convertir en majuscules)
        $this->iban = strtoupper(str_replace([' ', '-'], '', $iban));
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * Retourne l'IBAN formaté pour l'affichage (avec espaces)
     */
    public function getFormattedIban(): string
    {
        if (!$this->iban) {
            return '';
        }
        
        return chunk_split($this->iban, 4, ' ');
    }

    /**
     * Retourne l'IBAN masqué pour l'affichage public
     */
    public function getMaskedIban(): string
    {
        if (!$this->iban) {
            return '';
        }
        
        if (strlen($this->iban) < 8) {
            return str_repeat('*', strlen($this->iban));
        }
        
        return substr($this->iban, 0, 4) . str_repeat('*', strlen($this->iban) - 8) . substr($this->iban, -4);
    }
}
