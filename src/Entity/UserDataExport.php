<?php

namespace App\Entity;

use App\Repository\UserDataExportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserDataExportRepository::class)]
#[ORM\Table(name: 'user_data_export')]
class UserDataExport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $exportedAt = null;

    #[ORM\Column]
    private ?int $recordsCount = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $exportedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->exportedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExportedAt(): ?\DateTimeInterface
    {
        return $this->exportedAt;
    }

    public function setExportedAt(\DateTimeInterface $exportedAt): static
    {
        $this->exportedAt = $exportedAt;

        return $this;
    }

    public function getRecordsCount(): ?int
    {
        return $this->recordsCount;
    }

    public function setRecordsCount(int $recordsCount): static
    {
        $this->recordsCount = $recordsCount;

        return $this;
    }

    public function getExportedBy(): ?string
    {
        return $this->exportedBy;
    }

    public function setExportedBy(?string $exportedBy): static
    {
        $this->exportedBy = $exportedBy;

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

    public function __toString(): string
    {
        return sprintf(
            'Export du %s (%d enregistrements)',
            $this->exportedAt ? $this->exportedAt->format('d/m/Y H:i:s') : 'N/A',
            $this->recordsCount ?? 0
        );
    }
}
