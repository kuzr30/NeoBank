<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\Transaction;
use App\Enum\TransactionTypeEnum;
use App\Exception\InsufficientFundsException;
use App\Exception\NegativeBalanceException;
use Psr\Log\LoggerInterface;

class TransactionValidatorService
{
    public function __construct(
        private LoggerInterface $logger,
        private ProfessionalTranslationService $translationService
    ) {
    }

    /**
     * Valide qu'une transaction peut être effectuée
     */
    public function validateTransaction(Account $account, string $amount, string $type): void
    {
        $this->logger->info('Validation de transaction', [
            'account_id' => $account->getId(),
            'amount' => $amount,
            'type' => $type,
            'current_balance' => $account->getBalance()
        ]);

        // Convertir en float pour les calculs
        $transactionAmount = (float) $amount;
        $currentBalance = (float) $account->getBalance();

        // Validation des montants négatifs
        if ($transactionAmount <= 0) {
            throw new \InvalidArgumentException($this->translationService->tp('exceptions.positive_amount_required', [], 'transaction_validator_service'));
        }

        // Pour les débits, vérifier les règles spéciales
        if ($type === TransactionTypeEnum::DEBIT->value) {
            $this->validateDebitTransaction($account, $transactionAmount, $currentBalance);
        }
    }

    /**
     * Valide spécifiquement les transactions de débit
     */
    private function validateDebitTransaction(Account $account, float $amount, float $currentBalance): void
    {
        // Règle 1: Si solde = 0, aucune transaction de débit autorisée
        if ($currentBalance == 0) {
            $this->logger->warning($this->translationService->tp('log_messages.empty_account_attempt', [], 'transaction_validator_service'), [
                'account_id' => $account->getId(),
                'amount' => $amount
            ]);
            throw new InsufficientFundsException(
                $this->translationService->tp('user_messages.empty_account_debit', [], 'transaction_validator_service')
            );
        }

        // Règle 2: Le solde ne peut jamais devenir négatif (pas de découvert)
        $newBalance = $currentBalance - $amount;
        if ($newBalance < 0) {
            $this->logger->warning($this->translationService->tp('log_messages.negative_balance_attempt', [], 'transaction_validator_service'), [
                'account_id' => $account->getId(),
                'amount' => $amount,
                'current_balance' => $currentBalance,
                'would_be_balance' => $newBalance
            ]);
            throw new NegativeBalanceException(
                $this->translationService->tp('user_messages.insufficient_funds', [
                    'currentBalance' => sprintf('%.2f', $currentBalance),
                    'amount' => sprintf('%.2f', $amount)
                ], 'transaction_validator_service')
            );
        }

        $this->logger->info($this->translationService->tp('log_messages.debit_validation_success', [], 'transaction_validator_service'), [
            'account_id' => $account->getId(),
            'amount' => $amount,
            'new_balance' => $newBalance
        ]);
    }

    /**
     * Vérifie si un compte peut effectuer des transactions
     */
    public function canAccountTransact(Account $account): bool
    {
        // Le compte doit être actif
        if ($account->getStatus() !== 'active') {
            return false;
        }

        // Pour les crédits, toujours autorisé
        // Pour les débits, il faut un solde > 0
        return true; // Cette méthode sera appelée avant validateTransaction
    }

    /**
     * Retourne le montant maximum pouvant être débité
     */
    public function getMaxDebitAmount(Account $account): float
    {
        $currentBalance = (float) $account->getBalance();
        
        // Pas de découvert autorisé, donc le max = solde actuel
        return max(0, $currentBalance);
    }
}
