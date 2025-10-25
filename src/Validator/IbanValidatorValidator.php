<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class IbanValidatorValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof IbanValidator) {
            throw new UnexpectedTypeException($constraint, IbanValidator::class);
        }

        // null et chaîne vide sont considérés comme valides
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        // Inclure la bibliothèque php-iban
        require_once dirname(__DIR__, 2) . '/vendor/globalcitizen/php-iban/php-iban.php';

        // Nettoyer l'entrée (enlever les espaces, convertir en majuscules)
        $cleanIban = strtoupper(str_replace([' ', '-'], '', $value));

        // Vérifier le format français si requis
        if ($constraint->frenchOnly && !preg_match('/^FR\d{25}$/', $cleanIban)) {
            $this->context->buildViolation($constraint->invalidFormatMessage)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
            return;
        }

        // Validation avec php-iban
        $machineFormatOnly = $constraint->strictMode;
        
        if (!verify_iban($cleanIban, $machineFormatOnly)) {
            // Vérifier si c'est un problème de checksum ou de format
            if (preg_match('/^[A-Z]{2}\d+$/', $cleanIban)) {
                // Format correct mais checksum invalide
                $this->context->buildViolation($constraint->invalidChecksumMessage)
                    ->setParameter('{{ value }}', $value)
                    ->addViolation();
            } else {
                // Format général invalide
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $value)
                    ->addViolation();
            }
            return;
        }

        // Validation supplémentaire du checksum
        if (!iban_verify_checksum($cleanIban)) {
            $this->context->buildViolation($constraint->invalidChecksumMessage)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
