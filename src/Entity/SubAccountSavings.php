<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sub_account_savings')]
class SubAccountSavings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $amount = '0.00';

    #[ORM\OneToOne(targetEntity: Account::class, inversedBy: 'subAccountSavings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $account = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): static
    {
        $this->account = $account;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Ajouter un montant au sous-compte épargne
     */
    public function credit(string $amount): void
    {
        $newAmount = (float) $this->amount + (float) $amount;
        $this->setAmount((string) $newAmount);
    }

    /**
     * Débiter un montant du sous-compte épargne
     */
    public function debit(string $amount): void
    {
        $currentAmount = (float) $this->amount;
        $debitAmount = (float) $amount;
        
        if ($debitAmount > $currentAmount) {
            throw new \Exception(sprintf(
                'Montant insuffisant dans le compte épargne. Disponible: %.2f €, Demandé: %.2f €',
                $currentAmount,
                $debitAmount
            ));
        }
        
        $newAmount = $currentAmount - $debitAmount;
        $this->setAmount((string) $newAmount);
    }
}
