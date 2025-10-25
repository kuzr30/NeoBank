<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class IbanValidator extends Constraint
{
    public string $message = 'validators.iban_validator.message';
    public string $invalidFormatMessage = 'validators.iban_validator.invalid_format';
    public string $invalidChecksumMessage = 'validators.iban_validator.invalid_checksum';
    public bool $strictMode = false; // Si true, accepte uniquement le format machine (sans espaces)
    public bool $frenchOnly = true; // Si true, accepte uniquement les IBAN français
}
