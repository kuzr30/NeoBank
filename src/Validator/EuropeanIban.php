<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class EuropeanIban extends Constraint
{
    public string $message = 'validators.european_iban.message';
    public string $invalidFormatMessage = 'validators.european_iban.invalid_format';
    public string $invalidChecksumMessage = 'validators.european_iban.invalid_checksum';
    public string $nonEuropeanMessage = 'validators.european_iban.non_european';
    public bool $strictMode = false; // Si true, accepte uniquement le format machine (sans espaces)
}
