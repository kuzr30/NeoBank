<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AssuranceType;
use App\Enum\DemandeDevisStatusEnum;
use App\Repository\DemandeDevisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DemandeDevisRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'demande_devis')]
class DemandeDevis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $numeroDevis = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'validators.devis_form.lastname_required')]
    #[Assert\Length(max: 100, maxMessage: 'validators.devis_form.lastname_max')]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'validators.devis_form.firstname_required')]
    #[Assert\Length(max: 100, maxMessage: 'validators.devis_form.firstname_max')]
    private ?string $prenom = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'validators.devis_form.email_required')]
    #[Assert\Email(message: 'validators.devis_form.email_invalid')]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'validators.devis_form.phone_required')]
    #[Assert\Tel(message: 'validators.devis_form.phone_invalid')]
    private ?string $telephone = null;




    #[ORM\Column(enumType: AssuranceType::class)]
    #[Assert\NotNull(message: 'validators.devis_form.insurance_type_required')]
    private ?AssuranceType $typeAssurance = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(
        choices: ['telephone', 'email', 'indifferent'],
        message: 'validators.devis_form.contact_preference_invalid'
    )]
    private string $preferenceContact = 'email';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: 'validators.devis_form.comments_max')]
    private ?string $commentaires = null;



    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(enumType: DemandeDevisStatusEnum::class)]
    private DemandeDevisStatusEnum $statut = DemandeDevisStatusEnum::EN_ATTENTE;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroDevis(): ?string
    {
        return $this->numeroDevis;
    }

    public function setNumeroDevis(string $numeroDevis): static
    {
        $this->numeroDevis = $numeroDevis;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }




    public function getTypeAssurance(): ?AssuranceType
    {
        return $this->typeAssurance;
    }

    public function setTypeAssurance(AssuranceType $typeAssurance): static
    {
        $this->typeAssurance = $typeAssurance;
        return $this;
    }

    public function getPreferenceContact(): string
    {
        return $this->preferenceContact;
    }

    public function setPreferenceContact(string $preferenceContact): static
    {
        $this->preferenceContact = $preferenceContact;
        return $this;
    }

    public function getCommentaires(): ?string
    {
        return $this->commentaires;
    }

    public function setCommentaires(?string $commentaires): static
    {
        $this->commentaires = $commentaires;
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

    public function getStatut(): DemandeDevisStatusEnum
    {
        return $this->statut;
    }

    public function setStatut(DemandeDevisStatusEnum $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    #[ORM\PrePersist]
    public function generateNumeroDevis(): void
    {
        if ($this->numeroDevis === null) {
            $this->numeroDevis = 'DEV-' . date('Y') . '-' . str_pad((string) rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        }

        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getNomComplet(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    public function getPreferenceContactLabel(): string
    {
        return match ($this->preferenceContact) {
            'telephone' => 'Par téléphone',
            'email' => 'Par email',
            'indifferent' => 'Indifférent',
            default => 'Non défini'
        };
    }

    // Méthodes utilitaires pour les statuts

    public function getStatutLabel(): string
    {
        return $this->statut->getLabel();
    }

    public function isPending(): bool
    {
        return $this->statut->isPending();
    }

    public function isApproved(): bool
    {
        return $this->statut === DemandeDevisStatusEnum::APPROUVE;
    }

    public function isRejected(): bool
    {
        return $this->statut === DemandeDevisStatusEnum::REFUSE;
    }

    public function canBeApproved(): bool
    {
        return $this->statut->canBeApproved();
    }

    public function canBeRejected(): bool
    {
        return $this->statut->canBeRejected();
    }

    public function canCreateContract(): bool
    {
        return $this->statut->canCreateContract();
    }

    public function approve(): void
    {
        if (!$this->canBeApproved()) {
            throw new \InvalidArgumentException('Cette demande ne peut pas être approuvée dans son état actuel: ' . $this->statut->getLabel());
        }
        
        $this->statut = DemandeDevisStatusEnum::APPROUVE;
    }

    public function reject(): void
    {
        if (!$this->canBeRejected()) {
            throw new \InvalidArgumentException('Cette demande ne peut pas être refusée dans son état actuel: ' . $this->statut->getLabel());
        }
        
        $this->statut = DemandeDevisStatusEnum::REFUSE;
    }

    public function markAsProcessing(): void
    {
        if ($this->statut !== DemandeDevisStatusEnum::EN_ATTENTE) {
            throw new \InvalidArgumentException('Seules les demandes en attente peuvent être mises en cours de traitement');
        }
        
        $this->statut = DemandeDevisStatusEnum::EN_COURS;
    }

    public function markAsProcessed(): void
    {
        if (!in_array($this->statut, [DemandeDevisStatusEnum::EN_ATTENTE, DemandeDevisStatusEnum::EN_COURS], true)) {
            throw new \InvalidArgumentException('Cette demande ne peut pas être marquée comme traitée dans son état actuel');
        }
        
        $this->statut = DemandeDevisStatusEnum::TRAITE;
    }

    public function __toString(): string
    {
        return sprintf('%s - %s (%s)', 
            $this->numeroDevis ?? 'N/A', 
            $this->getNomComplet(), 
            $this->typeAssurance?->getLabel() ?? 'Type non défini'
        );
    }
}
