<?php

namespace App\Manager;

use App\Entity\User;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Repository\LoanRepository;

class CardManager
{
    public function __construct(
        private AccountRepository $accountRepository,
        private TransactionRepository $transactionRepository,
        private LoanRepository $loanRepository
    ) {
    }

    /**
     * Récupère toutes les cartes d'un utilisateur
     */
    public function getUserCards(User $user): array
    {
        $accounts = $this->accountRepository->findActiveByUser($user);
        $cards = [];
        
        foreach ($accounts as $account) {
            $accountCards = $account->getCards();
            foreach ($accountCards as $card) {
                $cards[] = $card;
            }
        }
        
        return $cards;
    }

    /**
     * Récupère les données nécessaires pour le dashboard
     */
    public function getDashboardData(User $user, string $activeTab = 'cartes'): array
    {
        $accounts = $this->accountRepository->findActiveByUser($user);
        $totalBalance = array_sum(array_map(fn($account) => $account->getBalance(), $accounts));
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
