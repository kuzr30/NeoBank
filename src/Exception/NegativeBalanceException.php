<?php

namespace App\Exception;

class NegativeBalanceException extends \Exception
{
    public function __construct(string $message = 'Cette transaction créerait un solde négatif, ce qui n\'est pas autorisé.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
