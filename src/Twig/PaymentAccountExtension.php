<?php

namespace App\Twig;

use App\Repository\PaymentAccountRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PaymentAccountExtension extends AbstractExtension
{
    public function __construct(
        private PaymentAccountRepository $paymentAccountRepository
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('payment_account', [$this, 'getPaymentAccount']),
            new TwigFunction('payment_account_name', [$this, 'getPaymentAccountName']),
            new TwigFunction('payment_iban', [$this, 'getPaymentIban']),
            new TwigFunction('payment_bic', [$this, 'getPaymentBic']),
        ];
    }

    /**
     * Récupère l'objet PaymentAccount complet
     */
    public function getPaymentAccount(): ?object
    {
        return $this->paymentAccountRepository->getPaymentAccount();
    }

    /**
     * Récupère le nom du compte de paiement
     */
    public function getPaymentAccountName(): ?string
    {
        $account = $this->paymentAccountRepository->getPaymentAccount();
        return $account?->getAccountName();
    }

    /**
     * Récupère l'IBAN du compte de paiement
     */
    public function getPaymentIban(): ?string
    {
        $account = $this->paymentAccountRepository->getPaymentAccount();
        return $account?->getIban();
    }

    /**
     * Récupère le BIC du compte de paiement
     */
    public function getPaymentBic(): ?string
    {
        $account = $this->paymentAccountRepository->getPaymentAccount();
        return $account?->getBic();
    }
}
