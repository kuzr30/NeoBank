<?php

namespace App\Manager;

use App\Entity\User;
use App\Entity\Account;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Repository\LoanRepository;

class AccountManager
{
    public function __construct(
        private AccountRepository $accountRepository,
        private TransactionRepository $transactionRepository,
        private LoanRepository $loanRepository
    ) {
    }

    /**
     * Récupère tous les comptes actifs d'un utilisateur
     */
    public function getUserAccounts(User $user): array
    {
        return $this->accountRepository->findActiveByUser($user);
    }

    /**
     * Calcule le solde total de tous les comptes d'un utilisateur
     */
    public function getTotalBalance(User $user): float
    {
        $accounts = $this->getUserAccounts($user);
        return array_sum(array_map(fn($account) => $account->getBalance(), $accounts));
    }

    /**
     * Récupère les transactions d'un compte spécifique
     */
    public function getAccountTransactions(Account $account, int $limit = 50): array
    {
        return $this->transactionRepository->findByAccount($account, $limit);
    }

    /**
     * Récupère les données nécessaires pour le dashboard
     */
    public function getDashboardData(User $user, string $activeTab = 'comptes'): array
    {
        $accounts = $this->getUserAccounts($user);
        $totalBalance = $this->getTotalBalance($user);
        $recentTransactions = $this->transactionRepository->findRecentTransactionsByUser($user, 5);
        $loans = $this->loanRepository->findActiveByUser($user);
        
        return [
            'user' => $user,
            'accounts' => $accounts,
            'total_balance' => $totalBalance,
            'recent_transactions' => $recentTransactions,
            'loans' => $loans,
            'activeTab' => $activeTab,
        ];
    }
}
