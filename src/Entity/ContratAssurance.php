<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AssuranceType;
use App\Enum\ContratAssuranceStatusEnum;
use App\Repository\ContratAssuranceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContratAssuranceRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'contrat_assurance')]
class ContratAssurance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToOne(targetEntity: DemandeDevis::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?DemandeDevis $demandeDevis = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $numeroContrat = null;

    #[ORM\Column(enumType: AssuranceType::class)]
    private ?AssuranceType $typeAssurance = null;

    #[ORM\Column(enumType: ContratAssuranceStatusEnum::class)]
    private ContratAssuranceStatusEnum $statut = ContratAssuranceStatusEnum::ACTIF;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    #[Assert\PositiveOrZero(message: 'La prime mensuelle doit être positive')]
    private ?string $primeMensuelle = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le montant de couverture doit être positif')]
    private ?string $montantCouverture = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Les frais d\'assurance doivent être positifs')]
    private ?string $fraisAssurance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Les frais de dossier doivent être positifs')]
    private ?string $fraisDossier = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateActivation = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateExpiration = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateResiliation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $conditionsParticulieres = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $noteAdmin = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

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

    public function getDemandeDevis(): ?DemandeDevis
    {
        return $this->demandeDevis;
    }

    public function setDemandeDevis(?DemandeDevis $demandeDevis): static
    {
        $this->demandeDevis = $demandeDevis;
        
        // Synchroniser le type d'assurance avec la demande de devis
        if ($demandeDevis) {
            $this->typeAssurance = $demandeDevis->getTypeAssurance();
        }
        
        return $this;
    }

    public function getNumeroContrat(): ?string
    {
        return $this->numeroContrat;
    }

    public function setNumeroContrat(string $numeroContrat): static
    {
        $this->numeroContrat = $numeroContrat;
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

    public function getStatut(): ContratAssuranceStatusEnum
    {
        return $this->statut;
    }

    public function setStatut(ContratAssuranceStatusEnum $statut): static
    {
        $this->statut = $statut;
        
        // Mise à jour automatique de la date de résiliation
        if ($statut === ContratAssuranceStatusEnum::RESILIE && !$this->dateResiliation) {
            $this->dateResiliation = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getPrimeMensuelle(): ?string
    {
        return $this->primeMensuelle;
    }

    public function setPrimeMensuelle(string $primeMensuelle): static
    {
        $this->primeMensuelle = $primeMensuelle;
        return $this;
    }

    public function getMontantCouverture(): ?string
    {
        return $this->montantCouverture;
    }

    public function setMontantCouverture(?string $montantCouverture): static
    {
        $this->montantCouverture = $montantCouverture;
        return $this;
    }

    public function getFraisAssurance(): ?string
    {
        return $this->fraisAssurance;
    }

    public function setFraisAssurance(?string $fraisAssurance): static
    {
        $this->fraisAssurance = $fraisAssurance;
        return $this;
    }

    public function getFraisDossier(): ?string
    {
        return $this->fraisDossier;
    }

    public function setFraisDossier(?string $fraisDossier): static
    {
        $this->fraisDossier = $fraisDossier;
        return $this;
    }

    public function getDateActivation(): ?\DateTimeImmutable
    {
        return $this->dateActivation;
    }

    public function setDateActivation(\DateTimeImmutable $dateActivation): static
    {
        $this->dateActivation = $dateActivation;
        return $this;
    }

    public function getDateExpiration(): ?\DateTimeImmutable
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(?\DateTimeImmutable $dateExpiration): static
    {
        $this->dateExpiration = $dateExpiration;
        return $this;
    }

    public function getDateResiliation(): ?\DateTimeImmutable
    {
        return $this->dateResiliation;
    }

    public function setDateResiliation(?\DateTimeImmutable $dateResiliation): static
    {
        $this->dateResiliation = $dateResiliation;
        return $this;
    }

    public function getConditionsParticulieres(): ?string
    {
        return $this->conditionsParticulieres;
    }

    public function setConditionsParticulieres(?string $conditionsParticulieres): static
    {
        $this->conditionsParticulieres = $conditionsParticulieres;
        return $this;
    }

    public function getNoteAdmin(): ?string
    {
        return $this->noteAdmin;
    }

    public function setNoteAdmin(?string $noteAdmin): static
    {
        $this->noteAdmin = $noteAdmin;
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

    #[ORM\PrePersist]
    public function generateNumeroContrat(): void
    {
        if ($this->numeroContrat === null) {
            $typeCode = match($this->typeAssurance) {
                AssuranceType::AUTO => 'AUT',
                AssuranceType::HABITATION => 'HAB',
                AssuranceType::SANTE => 'SAN',
                AssuranceType::VIE => 'VIE',
                AssuranceType::PRET => 'PRT',
                AssuranceType::VOYAGE => 'VOY',
                AssuranceType::PRO => 'PRO',
                AssuranceType::CYBER => 'CYB',
                AssuranceType::DECENNALE => 'DEC',
                AssuranceType::RC => 'RCC',
                AssuranceType::FLOTTE => 'FLT',
                AssuranceType::GARAGE => 'GAR',
                default => 'ASS'
            };
            
            $this->numeroContrat = $typeCode . '-' . date('Y') . '-' . str_pad((string) rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        }

        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }

        if ($this->dateActivation === null) {
            $this->dateActivation = new \DateTimeImmutable();
        }
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Méthodes utilitaires

    public function isActif(): bool
    {
        return $this->statut === ContratAssuranceStatusEnum::ACTIF;
    }

    public function isSuspendu(): bool
    {
        return $this->statut === ContratAssuranceStatusEnum::SUSPENDU;
    }

    public function isResilie(): bool
    {
        return $this->statut === ContratAssuranceStatusEnum::RESILIE;
    }

    public function isExpire(): bool
    {
        if ($this->statut === ContratAssuranceStatusEnum::EXPIRE) {
            return true;
        }
        
        // Vérifier si le contrat est expiré selon la date
        if ($this->dateExpiration && $this->dateExpiration < new \DateTimeImmutable()) {
            return true;
        }
        
        return false;
    }

    public function getStatutLabel(): string
    {
        return $this->statut->getLabel();
    }

    public function getDureeEnMois(): ?int
    {
        if (!$this->dateActivation) {
            return null;
        }
        
        $dateFin = $this->dateResiliation ?? $this->dateExpiration ?? new \DateTimeImmutable();
        $interval = $this->dateActivation->diff($dateFin);
        
        return ($interval->y * 12) + $interval->m;
    }

    public function getTotalPrimesPayees(): string
    {
        $dureeEnMois = $this->getDureeEnMois();
        if (!$dureeEnMois) {
            return '0.00';
        }
        
        return bcmul($this->primeMensuelle, (string) $dureeEnMois, 2);
    }

    public function getProchainePrimeDate(): ?\DateTimeImmutable
    {
        if (!$this->isActif() || !$this->dateActivation) {
            return null;
        }
        
        // Calculer la prochaine échéance mensuelle
        $now = new \DateTimeImmutable();
        $prochainePrime = $this->dateActivation;
        
        while ($prochainePrime <= $now) {
            $prochainePrime = $prochainePrime->modify('+1 month');
        }
        
        return $prochainePrime;
    }

    public function getNomCompletClient(): string
    {
        if (!$this->demandeDevis) {
            return $this->user ? $this->user->getFirstName() . ' ' . $this->user->getLastName() : 'Inconnu';
        }
        
        return $this->demandeDevis->getNomComplet();
    }
}