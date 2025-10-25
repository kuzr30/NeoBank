<?php

namespace App\Manager;

use App\Entity\User;
use App\Entity\BankAccount;
use App\Repository\BankAccountRepository;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Repository\LoanRepository;
use App\Repository\CompanySettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\RibAddedNotificationMessage;

class RibManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BankAccountRepository $bankAccountRepository,
        private AccountRepository $accountRepository,
        private TransactionRepository $transactionRepository,
        private LoanRepository $loanRepository,
        private MessageBusInterface $messageBus,
        private CompanySettingsRepository $companySettingsRepository
    ) {
    }

    /**
     * Récupère tous les RIBs actifs d'un utilisateur
     */
    public function getUserRibs(User $user): array
    {
        return $this->bankAccountRepository->findActiveByUser($user);
    }

    /**
     * Vérifie si un IBAN existe déjà pour un utilisateur
     */
    public function ibanExistsForUser(User $user, string $iban): bool
    {
        return $this->bankAccountRepository->ibanExistsForUser($user, $iban);
    }

    /**
     * Crée un nouveau RIB pour un utilisateur
     */
    public function createRib(BankAccount $bankAccount): void
    {
        $this->entityManager->persist($bankAccount);
        $this->entityManager->flush();

        // Récupérer les données de la société
        $companySettings = $this->companySettingsRepository->getCompanySettings();
        
        // Dispatcher le message pour l'envoi de l'email de notification
        $message = new RibAddedNotificationMessage(
            $bankAccount->getUser()->getEmail(),
            $bankAccount->getUser()->getFullName(),
            $bankAccount->getAccountName(),
            $bankAccount->getMaskedIban(),
            $bankAccount->getBankName(),
            $bankAccount->getUser()->getFirstName(),
            $companySettings?->getPhone(),
            $companySettings?->getEmail()
        );
        $this->messageBus->dispatch($message);
    }

    /**
     * Supprime un RIB (soft delete)
     */
    public function deleteRib(BankAccount $bankAccount): void
    {
        $bankAccount->setIsActive(false);
        $this->entityManager->flush();
    }

    /**
     * Récupère les données nécessaires pour le dashboard
     */
    public function getDashboardData(User $user, string $activeTab = 'ribs'): array
    {
        // Récupérer les données de base pour le dashboard
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
