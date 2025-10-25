<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DemandeDevisCreditAssociationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DemandeDevisCreditAssociationRepository::class)]
#[ORM\Table(name: 'demande_devis_credit_association')]
#[ORM\HasLifecycleCallbacks]
class DemandeDevisCreditAssociation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DemandeDevis::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DemandeDevis $demandeDevis = null;

    #[ORM\ManyToOne(targetEntity: CreditApplication::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CreditApplication $creditApplication = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDemandeDevis(): ?DemandeDevis
    {
        return $this->demandeDevis;
    }

    public function setDemandeDevis(?DemandeDevis $demandeDevis): static
    {
        $this->demandeDevis = $demandeDevis;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
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
     * Retourne une description lisible de l'association
     */
    public function getDescription(): string
    {
        return sprintf(
            'Devis %s associé au crédit %s',
            $this->demandeDevis?->getNumeroDevis() ?? 'N/A',
            $this->creditApplication?->getReferenceNumber() ?? 'N/A'
        );
    }
}