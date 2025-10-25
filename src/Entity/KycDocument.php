<?php

namespace App\Entity;

use App\Repository\KycDocumentRepository;
use App\Service\ProfessionalTranslationService;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: KycDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class KycDocument
{
    public const TYPE_IDENTITY = 'identity';
    public const TYPE_INCOME_PROOF = 'income_proof';
    public const TYPE_ADDRESS_PROOF = 'address_proof';
    public const TYPE_SELFIE = 'selfie';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: KycSubmission::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?KycSubmission $kycSubmission = null;

    #[ORM\Column(length: 50)]
    private string $type;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    #[ORM\Column(length: 50)]
    private ?string $mimeType = null;

    #[ORM\Column]
    private ?int $fileSize = null;

    #[Vich\UploadableField(mapping: 'kyc_documents', fileNameProperty: 'filename', originalName: 'originalName', mimeType: 'mimeType', size: 'fileSize')]
    #[Assert\File(
        maxSize: '4M',
        mimeTypes: ['image/jpeg', 'image/png', 'application/pdf'],
        mimeTypesMessage: 'Veuillez télécharger un fichier valide (PDF, JPG, PNG)'
    )]
    private ?File $documentFile = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $uploadedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->uploadedAt = new \DateTimeImmutable();
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

    public function getKycSubmission(): ?KycSubmission
    {
        return $this->kycSubmission;
    }

    public function setKycSubmission(?KycSubmission $kycSubmission): static
    {
        $this->kycSubmission = $kycSubmission;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;
        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(?string $originalName): static
    {
        $this->originalName = $originalName;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): static
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getDocumentFile(): ?File
    {
        return $this->documentFile;
    }

    public function setDocumentFile(?File $documentFile = null): static
    {
        $this->documentFile = $documentFile;

        if ($documentFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getUploadedAt(): ?\DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeImmutable $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;
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

    public function getTypeLabel(?ProfessionalTranslationService $translator = null): string
    {
        if ($translator) {
            return match($this->type) {
                self::TYPE_IDENTITY => $translator->trans('kyc_document.types.identity', [], 'kyc_document'),
                self::TYPE_INCOME_PROOF => $translator->trans('kyc_document.types.income_proof', [], 'kyc_document'),
                self::TYPE_ADDRESS_PROOF => $translator->trans('kyc_document.types.address_proof', [], 'kyc_document'),
                self::TYPE_SELFIE => $translator->trans('kyc_document.types.selfie', [], 'kyc_document'),
                default => $translator->trans('kyc_document.types.unknown', [], 'kyc_document')
            };
        }
        
        // Fallback pour la compatibilité
        return match($this->type) {
            self::TYPE_IDENTITY => 'Pièce d\'identité',
            self::TYPE_INCOME_PROOF => 'Justificatif de revenus',
            self::TYPE_ADDRESS_PROOF => 'Justificatif de domicile',
            self::TYPE_SELFIE => 'Selfie',
            default => 'Document inconnu'
        };
    }

    public static function getTypeChoices(?ProfessionalTranslationService $translator = null): array
    {
        if ($translator) {
            return [
                $translator->trans('kyc_document.types.identity', [], 'kyc_document') => self::TYPE_IDENTITY,
                $translator->trans('kyc_document.types.income_proof', [], 'kyc_document') => self::TYPE_INCOME_PROOF,
                $translator->trans('kyc_document.types.address_proof', [], 'kyc_document') => self::TYPE_ADDRESS_PROOF,
                $translator->trans('kyc_document.types.selfie', [], 'kyc_document') => self::TYPE_SELFIE,
            ];
        }
        
        // Fallback pour la compatibilité
        return [
            'Pièce d\'identité' => self::TYPE_IDENTITY,
            'Justificatif de revenus' => self::TYPE_INCOME_PROOF,
            'Justificatif de domicile' => self::TYPE_ADDRESS_PROOF,
            'Selfie' => self::TYPE_SELFIE,
        ];
    }

    public function getFormattedFileSize(): string
    {
        if (!$this->fileSize) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->fileSize;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isImage(): bool
    {
        return $this->mimeType && str_starts_with($this->mimeType, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mimeType === 'application/pdf';
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', 
            $this->getTypeLabel(),
            $this->originalName ?? 'Document'
        );
    }
}
