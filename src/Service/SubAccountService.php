<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\SubAccountCredit;
use App\Entity\SubAccountCard;
use App\Entity\SubAccountSavings;
use App\Entity\SubAccountInsurance;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class SubAccountService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProfessionalTranslationService $translationService
    ) {}

    /**
     * Crée tous les sous-comptes pour un compte principal
     */
    public function createSubAccountsForAccount(Account $account): void
    {
        // Créer le sous-compte crédit s'il n'existe pas
        if (!$account->getSubAccountCredit()) {
            $subAccountCredit = new SubAccountCredit();
            $subAccountCredit->setAccount($account);
            $account->setSubAccountCredit($subAccountCredit);
            $this->entityManager->persist($subAccountCredit);
        }

        // Créer le sous-compte carte s'il n'existe pas
        if (!$account->getSubAccountCard()) {
            $subAccountCard = new SubAccountCard();
            $subAccountCard->setAccount($account);
            $account->setSubAccountCard($subAccountCard);
            $this->entityManager->persist($subAccountCard);
        }

        // Créer le sous-compte épargne s'il n'existe pas
        if (!$account->getSubAccountSavings()) {
            $subAccountSavings = new SubAccountSavings();
            $subAccountSavings->setAccount($account);
            $account->setSubAccountSavings($subAccountSavings);
            $this->entityManager->persist($subAccountSavings);
        }

        // Créer le sous-compte assurance s'il n'existe pas
        if (!$account->getSubAccountInsurance()) {
            $subAccountInsurance = new SubAccountInsurance();
            $subAccountInsurance->setAccount($account);
            $account->setSubAccountInsurance($subAccountInsurance);
            $this->entityManager->persist($subAccountInsurance);
        }

        $this->entityManager->flush();
    }

    /**
     * Obtient ou crée le sous-compte crédit pour un utilisateur
     */
    public function getCreditSubAccount(User $user): SubAccountCredit
    {
        // Trouver le compte principal de l'utilisateur
        $mainAccount = $this->entityManager->getRepository(Account::class)
            ->findOneBy(['owner' => $user, 'type' => 'checking']);

        if (!$mainAccount) {
            throw new \Exception($this->translationService->tp('exceptions.main_account_not_found', [
                'email' => $user->getEmail()
            ], 'sub_account_service'));
        }

        // Créer les sous-comptes s'ils n'existent pas
        $this->createSubAccountsForAccount($mainAccount);

        $creditSubAccount = $mainAccount->getSubAccountCredit();
        
        if (!$creditSubAccount) {
            throw new \Exception($this->translationService->tp('exceptions.credit_subaccount_creation_failed', [
                'email' => $user->getEmail()
            ], 'sub_account_service'));
        }

        return $creditSubAccount;
    }

    /**
     * Obtient tous les soldes des sous-comptes pour un utilisateur
     */
    public function getAllSubAccountBalances(User $user): array
    {
        $mainAccount = $this->entityManager->getRepository(Account::class)
            ->findOneBy(['owner' => $user, 'type' => 'checking']);

        if (!$mainAccount) {
            return [];
        }

        $this->createSubAccountsForAccount($mainAccount);

        return [
            'main_account' => (float) $mainAccount->getBalance(),
            'credit' => (float) ($mainAccount->getSubAccountCredit()?->getAmount() ?? '0.00'),
            'card' => (float) ($mainAccount->getSubAccountCard()?->getAmount() ?? '0.00'),
            'savings' => (float) ($mainAccount->getSubAccountSavings()?->getAmount() ?? '0.00'),
            'insurance' => (float) ($mainAccount->getSubAccountInsurance()?->getAmount() ?? '0.00')
        ];
    }

    /**
     * Calcule le total de tous les comptes d'un utilisateur
     */
    public function getTotalBalance(User $user): float
    {
        $balances = $this->getAllSubAccountBalances($user);
        return array_sum($balances);
    }
}
