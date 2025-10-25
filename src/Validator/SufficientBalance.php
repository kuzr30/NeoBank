<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class SufficientBalance extends Constraint
{
    public string $message = 'validators.sufficient_balance.message';
    
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
