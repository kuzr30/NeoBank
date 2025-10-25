<?php

namespace App\Exception;

class InsufficientFundsException extends \Exception
{
    public function __construct(string $message = 'Fonds insuffisants pour effectuer cette transaction.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
