<?php

namespace App\Entity;

use App\Repository\ContractFeeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContractFeeRepository::class)]
#[ORM\Table(name: 'contract_fees')]
class ContractFee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CreditApplication::class, inversedBy: 'contractFees')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CreditApplication $creditApplication = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private ?string $amount = null;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s: %s€', $this->name, number_format((float)$this->amount, 2));
    }
}
