<?php

namespace App\Entity;

use App\Enum\EmailStatus;
use App\Enum\EmailTemplateType;
use App\Repository\ScheduledEmailRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduledEmailRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ScheduledEmail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: EmailTemplateType::class)]
    private EmailTemplateType $templateType;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $recipient;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $reasons = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $scheduledFor = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $sentAt = null;

    #[ORM\Column(enumType: EmailStatus::class)]
    private EmailStatus $status = EmailStatus::PENDING;

    #[ORM\Column(length: 5)]
    private string $locale;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $customMessage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $amount = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTemplateType(): EmailTemplateType
    {
        return $this->templateType;
    }

    public function setTemplateType(EmailTemplateType $templateType): static
    {
        $this->templateType = $templateType;

        return $this;
    }

    public function getRecipient(): User
    {
        return $this->recipient;
    }

    public function setRecipient(User $recipient): static
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getReasons(): ?array
    {
        return $this->reasons;
    }

    public function setReasons(?array $reasons): static
    {
        $this->reasons = $reasons;

        return $this;
    }

    public function getScheduledFor(): ?\DateTimeInterface
    {
        return $this->scheduledFor;
    }

    public function setScheduledFor(?\DateTimeInterface $scheduledFor): static
    {
        $this->scheduledFor = $scheduledFor;

        return $this;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeInterface $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getStatus(): EmailStatus
    {
        return $this->status;
    }

    public function setStatus(EmailStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getCustomMessage(): ?string
    {
        return $this->customMessage;
    }

    public function setCustomMessage(?string $customMessage): static
    {
        $this->customMessage = $customMessage;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function isScheduled(): bool
    {
        return $this->scheduledFor !== null && $this->scheduledFor > new \DateTime();
    }

    public function isPending(): bool
    {
        return $this->status === EmailStatus::PENDING || $this->status === EmailStatus::SCHEDULED;
    }

    public function markAsSent(): void
    {
        $this->status = EmailStatus::SENT;
        $this->sentAt = new \DateTime();
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->status = EmailStatus::FAILED;
        $this->errorMessage = $errorMessage;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Virtual method for EasyAdmin display
     */
    public function getStatusLabel(): string
    {
        return $this->status->getLabel();
    }
}
