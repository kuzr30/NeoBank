<?php

namespace App\Manager;

use App\Entity\User;
use App\Entity\Loan;
use App\Repository\LoanRepository;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;

class LoanManager
{
    public function __construct(
        private LoanRepository $loanRepository,
        private AccountRepository $accountRepository,
        private TransactionRepository $transactionRepository
    ) {
    }

    /**
     * Récupère tous les prêts actifs d'un utilisateur
     */
    public function getUserLoans(User $user): array
    {
        return $this->loanRepository->findActiveByUser($user);
    }

    /**
     * Récupère tous les prêts d'un utilisateur par ses comptes
     */
    public function getAllUserLoans(User $user): array
    {
        $accounts = $this->accountRepository->findActiveByUser($user);
        $loans = [];
        
        foreach ($accounts as $account) {
            $accountLoans = $this->loanRepository->findByAccount($account);
            $loans = array_merge($loans, $accountLoans);
        }
        
        return $loans;
    }

    /**
     * Récupère les paiements d'un prêt
     */
    public function getLoanPayments(Loan $loan): array
    {
        return $loan->getPayments()->toArray();
    }

    /**
     * Récupère les données nécessaires pour le dashboard
     */
    public function getDashboardData(User $user, string $activeTab = 'credits'): array
    {
        $accounts = $this->accountRepository->findActiveByUser($user);
        $totalBalance = array_sum(array_map(fn($account) => $account->getBalance(), $accounts));
        $recentTransactions = $this->transactionRepository->findRecentTransactionsByUser($user, 5);
        $loans = $this->getUserLoans($user);
        
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
