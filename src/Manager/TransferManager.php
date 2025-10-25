<?php

namespace App\Manager;

use App\Entity\User;
use App\Entity\Transaction;
use App\Entity\SubAccountCredit;
use App\Message\SendCreditTransferVerificationCodeMessage;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Repository\LoanRepository;
use App\Service\ProfessionalTranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class TransferManager
{
    public function __construct(
        private AccountRepository $accountRepository,
        private TransactionRepository $transactionRepository,
        private LoanRepository $loanRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private ProfessionalTranslationService $translationService
    ) {
    }

    /**
     * Récupère tous les comptes d'un utilisateur pour les virements
     */
    public function getUserAccountsForTransfer(User $user): array
    {
        return $this->accountRepository->findActiveByUser($user);
    }

    /**
     * Récupère les données nécessaires pour le dashboard
     */
    public function getDashboardData(User $user, string $activeTab = 'virements'): array
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

    /**
     * Vérifie si l'utilisateur peut accéder au sous-compte crédit
     */
    public function canAccessSubAccountCredit(User $user, SubAccountCredit $subAccountCredit): bool
    {
        return $subAccountCredit->getAccount()->getOwner() === $user;
    }

    /**
     * Valide le montant du virement
     */
    public function validateTransferAmount(SubAccountCredit $subAccountCredit, string $amount): array
    {
        $errors = [];
        
        if (!is_numeric($amount) || (float) $amount <= 0) {
            $errors[] = $this->translationService->tp('validation.amount_must_be_positive', [], 'transfer_manager');
        }
        
        if ((float) $amount > (float) $subAccountCredit->getAmount()) {
            $errors[] = $this->translationService->tp('validation.insufficient_credit_amount', [], 'transfer_manager');
        }
        
        return $errors;
    }

    /**
     * Initie un virement avec code de vérification
     */
    public function initiateTransfer(SessionInterface $session, int $subAccountId, string $amount, User $user): string
    {
        // Récupérer le sous-compte crédit
        $subAccountCredit = $this->entityManager->getRepository(SubAccountCredit::class)->find($subAccountId);
        
        if (!$subAccountCredit) {
            throw new \InvalidArgumentException($this->translationService->tp('errors.sub_account_not_found', [], 'transfer_manager'));
        }

        // Générer un code de vérification
        $verificationCode = random_int(100000, 999999);
        
        // Stocker les informations de transfert en session
        $session->set('pending_credit_transfer', [
            'sub_account_id' => $subAccountId,
            'amount' => $amount,
            'code' => $verificationCode,
            'expires' => time() + 600 // 10 minutes
        ]);

        // Envoyer l'email avec le code de vérification
        $message = new SendCreditTransferVerificationCodeMessage(
            $user->getEmail(),
            $user->getFirstName() . ' ' . $user->getLastName(),
            (string) $verificationCode,
            (float) $amount,
            $subAccountCredit->getAccount()->getAccountNumber()
        );

        $this->messageBus->dispatch($message);

        return (string) $verificationCode;
    }

    /**
     * Vérifie le code de vérification
     */
    public function verifyTransferCode(SessionInterface $session, string $verificationCode): array
    {
        $pendingTransfer = $session->get('pending_credit_transfer');
        
        if (!$pendingTransfer) {
            return ['error' => $this->translationService->tp('errors.no_pending_transfer', [], 'transfer_manager')];
        }
        
        if ($pendingTransfer['expires'] < time()) {
            $session->remove('pending_credit_transfer');
            return ['error' => $this->translationService->tp('errors.verification_code_expired', [], 'transfer_manager')];
        }
        
        if ($verificationCode != $pendingTransfer['code']) {
            return ['error' => $this->translationService->tp('errors.verification_code_invalid', [], 'transfer_manager')];
        }
        
        return ['success' => true, 'transfer' => $pendingTransfer];
    }

    /**
     * Exécute le virement du crédit vers le compte principal
     */
    public function executeCreditTransfer(int $subAccountId, string $amount, User $user): void
    {
        // Récupérer le sous-compte crédit
        $subAccountCredit = $this->entityManager->getRepository(SubAccountCredit::class)->find($subAccountId);
        
        if (!$subAccountCredit || $subAccountCredit->getAccount()->getOwner() !== $user) {
            throw new \Exception($this->translationService->tp('errors.sub_account_not_found', [], 'transfer_manager'));
        }
        
        $account = $subAccountCredit->getAccount();
        $transferAmount = (float) $amount;
        
        // Vérifier encore une fois le solde
        if ($transferAmount > (float) $subAccountCredit->getAmount()) {
            throw new \Exception($this->translationService->tp('errors.insufficient_amount', [], 'transfer_manager'));
        }
        
        // Débiter le sous-compte crédit
        $subAccountCredit->debit($amount);
        
        // Créditer le compte principal
        $currentBalance = (float) $account->getBalance();
        $newBalance = $currentBalance + $transferAmount;
        $account->setBalance((string) $newBalance);
        
        // Créer la transaction de débit (sous-compte crédit)
        $debitTransaction = new Transaction();
        $debitTransaction->setAccount($account);
        $debitTransaction->setType('debit');
        $debitTransaction->setCategory('transfer');
        $debitTransaction->setAmount($amount);
        $debitTransaction->setDescription('credit_to_main_debit');
        $debitTransaction->setStatus('completed');
        $debitTransaction->setProcessedAt(new \DateTimeImmutable());
        $debitTransaction->setInitiatedBy($user);
        $debitTransaction->addMetadata('transfer_type', 'credit_to_main_debit');
        $debitTransaction->addMetadata('sub_account_credit_id', $subAccountCredit->getId());
        
        // Créer la transaction de crédit (compte principal)
        $creditTransaction = new Transaction();
        $creditTransaction->setAccount($account);
        $creditTransaction->setType('credit');
        $creditTransaction->setCategory('transfer');
        $creditTransaction->setAmount($amount);
        $creditTransaction->setDescription('credit_to_main_credit');
        $creditTransaction->setStatus('completed');
        $creditTransaction->setProcessedAt(new \DateTimeImmutable());
        $creditTransaction->setBalanceAfter((string) $newBalance);
        $creditTransaction->setInitiatedBy($user);
        $creditTransaction->addMetadata('transfer_type', 'credit_to_main_credit');
        $creditTransaction->addMetadata('related_transaction_id', $debitTransaction->getId());
        
        $this->entityManager->persist($subAccountCredit);
        $this->entityManager->persist($account);
        $this->entityManager->persist($debitTransaction);
        $this->entityManager->persist($creditTransaction);
        $this->entityManager->flush();
    }

    /**
     * Nettoie la session après le virement
     */
    public function clearPendingCreditTransfer(SessionInterface $session): void
    {
        $session->remove('pending_credit_transfer');
    }
}
