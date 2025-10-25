<?php

namespace App\Entity;

use App\Repository\KycSubmissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KycSubmissionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class KycSubmission
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_INCOMPLETE = 'incomplete';
    public const STATUS_REPLACED = 'replaced';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'kycSubmission')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $processedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNotes = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $submissionLocale = null;

    #[ORM\OneToMany(mappedBy: 'kycSubmission', targetEntity: KycDocument::class, cascade: ['persist', 'remove'])]
    private Collection $documents;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(?\DateTimeImmutable $submittedAt): static
    {
        $this->submittedAt = $submittedAt;
        return $this;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): static
    {
        $this->processedAt = $processedAt;
        return $this;
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

    public function getAdminNotes(): ?string
    {
        return $this->adminNotes;
    }

    public function setAdminNotes(?string $adminNotes): static
    {
        $this->adminNotes = $adminNotes;
        return $this;
    }

    /**
     * @return Collection<int, KycDocument>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(KycDocument $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setKycSubmission($this);
        }
        return $this;
    }

    public function removeDocument(KycDocument $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getKycSubmission() === $this) {
                $document->setKycSubmission(null);
            }
        }
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

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isIncomplete(): bool
    {
        return $this->status === self::STATUS_INCOMPLETE;
    }

    public function isReplaced(): bool
    {
        return $this->status === self::STATUS_REPLACED;
    }

    public function getDocumentByType(string $type): ?KycDocument
    {
        foreach ($this->documents as $document) {
            if ($document->getType() === $type) {
                return $document;
            }
        }
        return null;
    }

    public function hasRequiredDocuments(): bool
    {
        $requiredTypes = [
            KycDocument::TYPE_IDENTITY,
            KycDocument::TYPE_INCOME_PROOF,
            KycDocument::TYPE_ADDRESS_PROOF
        ];

        foreach ($requiredTypes as $type) {
            if (!$this->getDocumentByType($type)) {
                return false;
            }
        }
        return true;
    }

    public static function getStatusChoices(): array
    {
        return [
            'En attente' => self::STATUS_PENDING,
            'Approuvé' => self::STATUS_APPROVED,
            'Rejeté' => self::STATUS_REJECTED,
            'Incomplet' => self::STATUS_INCOMPLETE,
            'Remplacé' => self::STATUS_REPLACED,
        ];
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_APPROVED => 'Approuvé',
            self::STATUS_REJECTED => 'Rejeté',
            self::STATUS_INCOMPLETE => 'Incomplet',
            self::STATUS_REPLACED => 'Remplacé',
            default => 'Inconnu'
        };
    }

    public function getSubmissionLocale(): ?string
    {
        return $this->submissionLocale;
    }

    public function setSubmissionLocale(?string $submissionLocale): static
    {
        $this->submissionLocale = $submissionLocale;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('KYC #%d - %s (%s)', 
            $this->id, 
            $this->user?->getEmail() ?? 'N/A',
            $this->getStatusLabel()
        );
    }
}
