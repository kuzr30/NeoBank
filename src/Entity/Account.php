<?php

namespace App\Entity;

use App\Repository\AccountRepository;
use App\Entity\SubAccountCredit;
use App\Entity\SubAccountCard;
use App\Entity\SubAccountSavings;
use App\Entity\SubAccountInsurance;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[ORM\Table(name: 'accounts')]
class Account
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $accountNumber = null;

    #[ORM\Column(length: 34, unique: true)]
    private ?string $iban = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $balance = '0.00';

    #[ORM\Column(length: 50)]
    #[Assert\Choice(['checking'])]
    private ?string $type = 'checking';

    #[ORM\Column(length: 20)]
    #[Assert\Choice(['active', 'suspended', 'closed', 'pending'])]
    private ?string $status = 'active';

    #[ORM\Column(length: 3)]
    private ?string $currency = 'EUR';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $overdraftLimit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 4, nullable: true)]
    private ?string $interestRate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'accounts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\OneToMany(mappedBy: 'account', targetEntity: Transaction::class, cascade: ['persist', 'remove'])]
    private Collection $transactions;

    #[ORM\OneToMany(mappedBy: 'account', targetEntity: Card::class, cascade: ['persist', 'remove'])]
    private Collection $cards;

    #[ORM\OneToMany(mappedBy: 'account', targetEntity: Loan::class)]
    private Collection $loans;

    #[ORM\OneToOne(mappedBy: 'account', targetEntity: SubAccountCredit::class, cascade: ['persist', 'remove'])]
    private ?SubAccountCredit $subAccountCredit = null;

    #[ORM\OneToOne(mappedBy: 'account', targetEntity: SubAccountCard::class, cascade: ['persist', 'remove'])]
    private ?SubAccountCard $subAccountCard = null;

    #[ORM\OneToOne(mappedBy: 'account', targetEntity: SubAccountSavings::class, cascade: ['persist', 'remove'])]
    private ?SubAccountSavings $subAccountSavings = null;

    #[ORM\OneToOne(mappedBy: 'account', targetEntity: SubAccountInsurance::class, cascade: ['persist', 'remove'])]
    private ?SubAccountInsurance $subAccountInsurance = null;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->cards = new ArrayCollection();
        $this->loans = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        // Ne plus générer automatiquement le numéro de compte ici
        // Il sera généré dans AccountCreationService selon le pays
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(string $accountNumber): static
    {
        $this->accountNumber = $accountNumber;
        return $this;
    }

    private function generateAccountNumber(): void
    {
        $this->accountNumber = 'BKI' . str_pad(random_int(1, 99999999), 8, '0', STR_PAD_LEFT);
    }

    /**
     * Génère un numéro de compte basé sur l'IBAN déjà généré
     * Extrait une partie significative de l'IBAN pour créer le numéro de compte
     */
    public function generateAccountNumberFromIban(): void
    {
        if (!$this->iban) {
            // Fallback si pas d'IBAN - juste des chiffres
            $this->accountNumber = str_pad(random_int(1, 9999999999), 10, '0', STR_PAD_LEFT);
            return;
        }
        
        // Extraire les derniers 10 chiffres de l'IBAN pour le numéro de compte
        $ibanDigitsOnly = preg_replace('/[^0-9]/', '', $this->iban);
        $accountPart = substr($ibanDigitsOnly, -10); // 10 derniers chiffres
        
        // S'assurer qu'on a au moins 10 chiffres
        if (strlen($accountPart) < 10) {
            $accountPart = str_pad($accountPart, 10, '0', STR_PAD_LEFT);
        }
        
        $this->accountNumber = $accountPart;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(string $iban): static
    {
        $this->iban = $iban;
        return $this;
    }

    private function generateIban(): void
    {
        // Méthode dépréciée - L'IBAN est maintenant généré par IbanGeneratorService
        // en fonction du pays de l'utilisateur
        $this->iban = 'FR76' . str_pad(random_int(1, 9999999999999999), 16, '0', STR_PAD_LEFT);
    }

    /**
     * Définit l'IBAN (utilisé par le service IbanGeneratorService)
     */
    public function setGeneratedIban(string $iban): static
    {
        $this->iban = $iban;
        return $this;
    }

    public function getBalance(): ?string
    {
        return $this->balance;
    }

    public function setBalance(string $balance): static
    {
        $this->balance = $balance;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getOverdraftLimit(): ?string
    {
        return $this->overdraftLimit;
    }

    public function setOverdraftLimit(?string $overdraftLimit): static
    {
        $this->overdraftLimit = $overdraftLimit;
        return $this;
    }

    public function getInterestRate(): ?string
    {
        return $this->interestRate;
    }

    public function setInterestRate(?string $interestRate): static
    {
        $this->interestRate = $interestRate;
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

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTimeImmutable $closedAt): static
    {
        $this->closedAt = $closedAt;
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

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setAccount($this);
        }
        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            if ($transaction->getAccount() === $this) {
                $transaction->setAccount(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Card>
     */
    public function getCards(): Collection
    {
        return $this->cards;
    }

    public function addCard(Card $card): static
    {
        if (!$this->cards->contains($card)) {
            $this->cards->add($card);
            $card->setAccount($this);
        }
        return $this;
    }

    public function removeCard(Card $card): static
    {
        if ($this->cards->removeElement($card)) {
            if ($card->getAccount() === $this) {
                $card->setAccount(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Loan>
     */
    public function getLoans(): Collection
    {
        return $this->loans;
    }

    public function addLoan(Loan $loan): static
    {
        if (!$this->loans->contains($loan)) {
            $this->loans->add($loan);
            $loan->setAccount($this);
        }
        return $this;
    }

    public function removeLoan(Loan $loan): static
    {
        if ($this->loans->removeElement($loan)) {
            if ($loan->getAccount() === $this) {
                $loan->setAccount(null);
            }
        }
        return $this;
    }

    public function canDebit(string $amount): bool
    {
        $currentBalance = (float) $this->balance;
        $debitAmount = (float) $amount;
        
        // Règle 1: Si solde = 0, aucun débit autorisé
        if ($currentBalance == 0) {
            return false;
        }
        
        // Règle 2: Pas de découvert autorisé (solde ne peut pas devenir négatif)
        return $debitAmount <= $currentBalance;
    }

    public function debit(string $amount): void
    {
        if (!$this->canDebit($amount)) {
            if ((float) $this->balance == 0) {
                throw new \Exception('Votre compte a un solde de 0 €. Veuillez alimenter votre compte avant d\'effectuer cette opération.');
            } else {
                throw new \Exception(sprintf(
                    'Solde insuffisant. Votre solde actuel est de %.2f € et vous tentez de débiter %.2f €.',
                    (float) $this->balance,
                    (float) $amount
                ));
            }
        }
        
        $newBalance = (float) $this->balance - (float) $amount;
        $this->setBalance(number_format($newBalance, 2, '.', ''));
        
        $this->setUpdatedAt(new \DateTimeImmutable());
    }

    public function credit(string $amount): void
    {
        $newBalance = (float) $this->balance + (float) $amount;
        $this->setBalance(number_format($newBalance, 2, '.', ''));
        
        $this->setUpdatedAt(new \DateTimeImmutable());
    }

    public function getSubAccountCredit(): ?SubAccountCredit
    {
        return $this->subAccountCredit;
    }

    public function setSubAccountCredit(?SubAccountCredit $subAccountCredit): static
    {
        // Unset the owning side of the relation if necessary
        if ($subAccountCredit === null && $this->subAccountCredit !== null) {
            $this->subAccountCredit->setAccount(null);
        }

        // Set the owning side of the relation if necessary
        if ($subAccountCredit !== null && $subAccountCredit->getAccount() !== $this) {
            $subAccountCredit->setAccount($this);
        }

        $this->subAccountCredit = $subAccountCredit;
        return $this;
    }

    public function getSubAccountCard(): ?SubAccountCard
    {
        return $this->subAccountCard;
    }

    public function setSubAccountCard(?SubAccountCard $subAccountCard): static
    {
        if ($subAccountCard === null && $this->subAccountCard !== null) {
            $this->subAccountCard->setAccount(null);
        }

        if ($subAccountCard !== null && $subAccountCard->getAccount() !== $this) {
            $subAccountCard->setAccount($this);
        }

        $this->subAccountCard = $subAccountCard;
        return $this;
    }

    public function getSubAccountSavings(): ?SubAccountSavings
    {
        return $this->subAccountSavings;
    }

    public function setSubAccountSavings(?SubAccountSavings $subAccountSavings): static
    {
        if ($subAccountSavings === null && $this->subAccountSavings !== null) {
            $this->subAccountSavings->setAccount(null);
        }

        if ($subAccountSavings !== null && $subAccountSavings->getAccount() !== $this) {
            $subAccountSavings->setAccount($this);
        }

        $this->subAccountSavings = $subAccountSavings;
        return $this;
    }

    public function getSubAccountInsurance(): ?SubAccountInsurance
    {
        return $this->subAccountInsurance;
    }

    public function setSubAccountInsurance(?SubAccountInsurance $subAccountInsurance): static
    {
        if ($subAccountInsurance === null && $this->subAccountInsurance !== null) {
            $this->subAccountInsurance->setAccount(null);
        }

        if ($subAccountInsurance !== null && $subAccountInsurance->getAccount() !== $this) {
            $subAccountInsurance->setAccount($this);
        }

        $this->subAccountInsurance = $subAccountInsurance;
        return $this;
    }

    /**
     * Calcule le changement de solde du mois en cours
     */
    public function getMonthlyBalanceChange(): float
    {
        $startOfMonth = new \DateTime('first day of this month 00:00:00');
        $totalChange = 0.0;

        foreach ($this->transactions as $transaction) {
            if ($transaction->getCreatedAt() >= $startOfMonth) {
                if ($transaction->getDestinationAccount() === $this) {
                    // Crédit (argent reçu)
                    $totalChange += (float) $transaction->getAmount();
                } elseif ($transaction->getAccount() === $this) {
                    // Débit (argent envoyé)
                    $totalChange -= (float) $transaction->getAmount();
                }
            }
        }

        return $totalChange;
    }

    public function __toString(): string
    {
        $ownerName = $this->owner ? $this->owner->getFirstName() . ' ' . $this->owner->getLastName() : 'Sans propriétaire';
        return $this->accountNumber ? "Compte {$this->accountNumber} - {$ownerName}" : "Compte sans numéro - {$ownerName}";
    }
}
