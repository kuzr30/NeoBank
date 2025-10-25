<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class EuropeanIbanValidator extends ConstraintValidator
{
    // Codes pays européens acceptés pour les IBAN
    private const EUROPEAN_COUNTRIES = [
        'AD', // Andorre
        'AT', // Autriche
        'BE', // Belgique
        'BG', // Bulgarie
        'CH', // Suisse
        'CY', // Chypre
        'CZ', // République tchèque
        'DE', // Allemagne
        'DK', // Danemark
        'EE', // Estonie
        'ES', // Espagne
        'FI', // Finlande
        'FO', // Îles Féroé
        'FR', // France
        'GB', // Royaume-Uni
        'GI', // Gibraltar
        'GL', // Groenland
        'GR', // Grèce
        'HR', // Croatie
        'HU', // Hongrie
        'IE', // Irlande
        'IS', // Islande
        'IT', // Italie
        'LI', // Liechtenstein
        'LT', // Lituanie
        'LU', // Luxembourg
        'LV', // Lettonie
        'MC', // Monaco
        'ME', // Monténégro
        'MK', // Macédoine du Nord
        'MT', // Malte
        'NL', // Pays-Bas
        'NO', // Norvège
        'PL', // Pologne
        'PT', // Portugal
        'RO', // Roumanie
        'RS', // Serbie
        'SE', // Suède
        'SI', // Slovénie
        'SK', // Slovaquie
        'SM', // Saint-Marin
        'VA', // Vatican
        'XK', // Kosovo
    ];

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof EuropeanIban) {
            throw new UnexpectedTypeException($constraint, EuropeanIban::class);
        }

        // null et chaîne vide sont considérés comme valides
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        // Nettoyer l'entrée (enlever les espaces, convertir en majuscules)
        $cleanIban = strtoupper(str_replace([' ', '-'], '', $value));

        // Vérifier le format général de l'IBAN
        if (!preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]+$/', $cleanIban)) {
            $this->context->buildViolation($constraint->invalidFormatMessage)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
            return;
        }

        // Extraire le code pays
        $countryCode = substr($cleanIban, 0, 2);

        // Vérifier si le pays est européen
        if (!in_array($countryCode, self::EUROPEAN_COUNTRIES, true)) {
            $this->context->buildViolation($constraint->nonEuropeanMessage)
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
