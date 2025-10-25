<?php

namespace App\Entity;

use App\Repository\TransferAttemptRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransferAttemptRepository::class)]
#[ORM\Table(name: 'transfer_attempts')]
class TransferAttempt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Transfer::class, inversedBy: 'transferAttempts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Transfer $transfer = null;

    #[ORM\ManyToOne(targetEntity: TransferCode::class, inversedBy: 'transferAttempts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TransferCode $transferCode = null;

    #[ORM\Column(length: 9)]
    private ?string $attemptedCode = null;

    #[ORM\Column]
    private ?bool $isSuccess = false;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $attemptedAt = null;

    public function __construct()
    {
        $this->attemptedAt = new \DateTimeImmutable();
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

    public function getTransferCode(): ?TransferCode
    {
        return $this->transferCode;
    }

    public function setTransferCode(?TransferCode $transferCode): static
    {
        $this->transferCode = $transferCode;
        return $this;
    }

    public function getAttemptedCode(): ?string
    {
        return $this->attemptedCode;
    }

    public function setAttemptedCode(string $attemptedCode): static
    {
        $this->attemptedCode = strtoupper($attemptedCode);
        return $this;
    }

    public function isSuccess(): ?bool
    {
        return $this->isSuccess;
    }

    public function setIsSuccess(bool $isSuccess): static
    {
        $this->isSuccess = $isSuccess;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getAttemptedAt(): ?\DateTimeImmutable
    {
        return $this->attemptedAt;
    }

    public function setAttemptedAt(\DateTimeImmutable $attemptedAt): static
    {
        $this->attemptedAt = $attemptedAt;
        return $this;
    }
}
