<?php

namespace App\Entity;

use App\Repository\AmortizationScheduleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AmortizationScheduleRepository::class)]
class AmortizationSchedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'amortizationSchedules')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CreditApplication $creditApplication = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $month = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $paymentDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $monthlyPayment = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $principalPayment = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $interestPayment = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $remainingAmount = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(int $month): static
    {
        $this->month = $month;

        return $this;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(\DateTimeInterface $paymentDate): static
    {
        $this->paymentDate = $paymentDate;

        return $this;
    }

    public function getMonthlyPayment(): ?string
    {
        return $this->monthlyPayment;
    }

    public function setMonthlyPayment(string $monthlyPayment): static
    {
        $this->monthlyPayment = $monthlyPayment;

        return $this;
    }

    public function getPrincipalPayment(): ?string
    {
        return $this->principalPayment;
    }

    public function setPrincipalPayment(string $principalPayment): static
    {
        $this->principalPayment = $principalPayment;

        return $this;
    }

    public function getInterestPayment(): ?string
    {
        return $this->interestPayment;
    }

    public function setInterestPayment(string $interestPayment): static
    {
        $this->interestPayment = $interestPayment;

        return $this;
    }

    public function getRemainingAmount(): ?string
    {
        return $this->remainingAmount;
    }

    public function setRemainingAmount(string $remainingAmount): static
    {
        $this->remainingAmount = $remainingAmount;

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
}
