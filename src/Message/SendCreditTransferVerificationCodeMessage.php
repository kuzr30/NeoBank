<?php

namespace App\Message;

class SendCreditTransferVerificationCodeMessage
{
    private string $userEmail;
    private string $userName;
    private string $verificationCode;
    private float $amount;
    private string $sourceAccount;

    public function __construct(
        string $userEmail,
        string $userName,
        string $verificationCode,
        float $amount,
        string $sourceAccount
    ) {
        $this->userEmail = $userEmail;
        $this->userName = $userName;
        $this->verificationCode = $verificationCode;
        $this->amount = $amount;
        $this->sourceAccount = $sourceAccount;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getVerificationCode(): string
    {
        return $this->verificationCode;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getSourceAccount(): string
    {
        return $this->sourceAccount;
    }
}
