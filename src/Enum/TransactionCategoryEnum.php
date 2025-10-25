<?php

namespace App\Enum;

enum TransactionCategoryEnum: string
{
    case TRANSFER = 'transfer';
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    case PAYMENT = 'payment';
    case FEE = 'fee';
    case INTEREST = 'interest';
    case LOAN_PAYMENT = 'loan_payment';
    case CARD_PAYMENT = 'card_payment';
    case DIRECT_DEBIT = 'direct_debit';
    case CHECK = 'check';
    case CASH = 'cash';
    case ONLINE_PAYMENT = 'online_payment';
    case REFUND = 'refund';

    public function getLabel(): string
    {
        return match($this) {
            self::TRANSFER => 'enums.transaction_category.transfer',
            self::DEPOSIT => 'enums.transaction_category.deposit',
            self::WITHDRAWAL => 'enums.transaction_category.withdrawal',
            self::PAYMENT => 'enums.transaction_category.payment',
            self::FEE => 'enums.transaction_category.fee',
            self::INTEREST => 'enums.transaction_category.interest',
            self::LOAN_PAYMENT => 'enums.transaction_category.loan_payment',
            self::CARD_PAYMENT => 'enums.transaction_category.card_payment',
            self::DIRECT_DEBIT => 'enums.transaction_category.direct_debit',
            self::CHECK => 'enums.transaction_category.check',
            self::CASH => 'enums.transaction_category.cash',
            self::ONLINE_PAYMENT => 'enums.transaction_category.online_payment',
            self::REFUND => 'enums.transaction_category.refund',
        };
    }

    public static function getChoices(): array
    {
        return [
            'Virement' => self::TRANSFER->value,
            'Dépôt' => self::DEPOSIT->value,
            'Retrait' => self::WITHDRAWAL->value,
            'Paiement' => self::PAYMENT->value,
            'Frais' => self::FEE->value,
            'Intérêts' => self::INTEREST->value,
            'Remboursement de prêt' => self::LOAN_PAYMENT->value,
            'Paiement par carte' => self::CARD_PAYMENT->value,
            'Prélèvement automatique' => self::DIRECT_DEBIT->value,
            'Chèque' => self::CHECK->value,
            'Espèces' => self::CASH->value,
            'Paiement en ligne' => self::ONLINE_PAYMENT->value,
            'Remboursement' => self::REFUND->value,
        ];
    }
}
