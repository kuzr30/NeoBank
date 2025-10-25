<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\Table(name: 'documents')]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    #[ORM\Column(length: 500)]
    private ?string $filePath = null;

    #[ORM\Column(length: 100)]
    private ?string $mimeType = null;

    #[ORM\Column]
    #[Assert\Positive()]
    private ?int $fileSize = null;

    #[ORM\Column(length: 64)]
    private ?string $fileHash = null;

    #[ORM\Column(length: 100)]
    #[Assert\Choice([
        'identity_card', 'passport', 'driver_license', 'residence_permit',
        'proof_of_address', 'utility_bill', 'bank_statement', 'tax_return',
        'pay_slip', 'employment_contract', 'business_registration',
        'insurance_policy', 'property_deed', 'rental_agreement',
        'medical_certificate', 'academic_diploma', 'other'
    ])]
    private ?string $type = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(['personal', 'professional', 'financial', 'legal', 'medical'])]
    private ?string $category = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(['pending', 'verified', 'rejected', 'expired'])]
    private ?string $status = 'pending';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiryDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $verifiedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $verifiedBy = null;

    #[ORM\ManyToOne(targetEntity: CreditApplication::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?CreditApplication $creditApplication = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column]
    private ?bool $isConfidential = false;

    #[ORM\Column]
    private ?bool $isArchived = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): static
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getFileHash(): ?string
    {
        return $this->fileHash;
    }

    public function setFileHash(string $fileHash): static
    {
        $this->fileHash = $fileHash;
        return $this;
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
        $this->updatedAt = new \DateTimeImmutable();
        
        if ($status === 'verified' && !$this->verifiedAt) {
            $this->verifiedAt = new \DateTimeImmutable();
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

    public function getExpiryDate(): ?\DateTimeImmutable
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(?\DateTimeImmutable $expiryDate): static
    {
        $this->expiryDate = $expiryDate;
        return $this;
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(?\DateTimeImmutable $verifiedAt): static
    {
        $this->verifiedAt = $verifiedAt;
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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        return $this;
    }

    public function getVerifiedBy(): ?User
    {
        return $this->verifiedBy;
    }

    public function setVerifiedBy(?User $verifiedBy): static
    {
        $this->verifiedBy = $verifiedBy;
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

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): static
    {
        $this->rejectionReason = $rejectionReason;
        return $this;
    }

    public function isConfidential(): ?bool
    {
        return $this->isConfidential;
    }

    public function setIsConfidential(bool $isConfidential): static
    {
        $this->isConfidential = $isConfidential;
        return $this;
    }

    public function isArchived(): ?bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $isArchived): static
    {
        $this->isArchived = $isArchived;
        return $this;
    }

    public function isExpired(): bool
    {
        if (!$this->expiryDate) {
            return false;
        }
        
        return $this->expiryDate < new \DateTimeImmutable();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->expiryDate) {
            return false;
        }
        
        $warningDate = new \DateTimeImmutable("+{$days} days");
        return $this->expiryDate <= $warningDate;
    }

    public function getFileExtension(): ?string
    {
        if (!$this->originalName) {
            return null;
        }
        
        return strtolower(pathinfo($this->originalName, PATHINFO_EXTENSION));
    }

    public function getFormattedFileSize(): string
    {
        if (!$this->fileSize) {
            return '0 B';
        }
        
        $bytes = $this->fileSize;
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mimeType ?? '', 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mimeType === 'application/pdf';
    }

    public function isViewable(): bool
    {
        return $this->isImage() || $this->isPdf();
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'identity_card' => 'Carte d\'identité',
            'passport' => 'Passeport',
            'driver_license' => 'Permis de conduire',
            'residence_permit' => 'Titre de séjour',
            'proof_of_address' => 'Justificatif de domicile',
            'utility_bill' => 'Facture de services publics',
            'bank_statement' => 'Relevé bancaire',
            'tax_return' => 'Déclaration de revenus',
            'pay_slip' => 'Fiche de paie',
            'employment_contract' => 'Contrat de travail',
            'business_registration' => 'Extrait Kbis',
            'insurance_policy' => 'Police d\'assurance',
            'property_deed' => 'Acte de propriété',
            'rental_agreement' => 'Contrat de location',
            'medical_certificate' => 'Certificat médical',
            'academic_diploma' => 'Diplôme',
            default => 'Autre document',
        };
    }
}
