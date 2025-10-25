<?php

namespace App\Service;

/**
 * Service de génération de numéros de cartes bancaires valides
 * Utilise l'algorithme de Luhn pour générer des numéros conformes
 * 
 * Respecte le principe Single Responsibility (SOLID)
 */
class CardNumberGeneratorService
{
    // Préfixes des marques de cartes (IIN - Issuer Identification Number)
    private const CARD_PREFIXES = [
        'visa' => ['4'],
        'mastercard' => ['51', '52', '53', '54', '55', '22', '23', '24', '25', '26', '27']
    ];

    /**
     * Génère un numéro de carte valide pour une marque donnée
     */
    public function generateValidCardNumber(string $brand): string
    {
        $brand = strtolower($brand);
        
        if (!isset(self::CARD_PREFIXES[$brand])) {
            throw new \InvalidArgumentException("Marque de carte non supportée: {$brand}");
        }

        $prefixes = self::CARD_PREFIXES[$brand];
        $prefix = $prefixes[array_rand($prefixes)];
        
        // Longueur totale selon la marque
        $totalLength = ($brand === 'visa') ? 16 : 16; // Visa et Mastercard: 16 chiffres
        
        // Générer les chiffres manquants (sauf le dernier pour la clé de contrôle)
        $digitsNeeded = $totalLength - strlen($prefix) - 1;
        $randomDigits = '';
        
        for ($i = 0; $i < $digitsNeeded; $i++) {
            $randomDigits .= random_int(0, 9);
        }
        
        $partialNumber = $prefix . $randomDigits;
        
        // Calculer et ajouter la clé de contrôle avec l'algorithme de Luhn
        $checksum = $this->calculateLuhnChecksum($partialNumber);
        
        return $partialNumber . $checksum;
    }

    /**
     * Calcule la clé de contrôle selon l'algorithme de Luhn
     */
    private function calculateLuhnChecksum(string $partialNumber): int
    {
        $sum = 0;
        $length = strlen($partialNumber);
        
        // Parcourir de droite à gauche
        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $partialNumber[$length - 1 - $i];
            
            // Doubler chaque deuxième chiffre (en partant de la droite)
            if ($i % 2 === 1) {
                $digit *= 2;
                // Si le résultat > 9, soustraire 9
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            
            $sum += $digit;
        }
        
        // La clé de contrôle rend la somme multiple de 10
        return (10 - ($sum % 10)) % 10;
    }

    /**
     * Valide un numéro de carte avec l'algorithme de Luhn
     */
    public function validateCardNumber(string $cardNumber): bool
    {
        $cardNumber = preg_replace('/\s+/', '', $cardNumber); // Retirer les espaces
        
        if (!ctype_digit($cardNumber) || strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            return false;
        }
        
        $sum = 0;
        $length = strlen($cardNumber);
        
        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $cardNumber[$length - 1 - $i];
            
            if ($i % 2 === 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            
            $sum += $digit;
        }
        
        return $sum % 10 === 0;
    }

    /**
     * Formate un numéro de carte avec des espaces
     */
    public function formatCardNumber(string $cardNumber): string
    {
        $cardNumber = preg_replace('/\s+/', '', $cardNumber);
        return chunk_split($cardNumber, 4, ' ');
    }

    /**
     * Détecte la marque d'une carte à partir de son numéro
     */
    public function detectCardBrand(string $cardNumber): ?string
    {
        $cardNumber = preg_replace('/\s+/', '', $cardNumber);
        
        if (str_starts_with($cardNumber, '4')) {
            return 'visa';
        }
        
        // Mastercard
        $firstTwo = substr($cardNumber, 0, 2);
        if (in_array($firstTwo, ['51', '52', '53', '54', '55']) || 
            (intval($firstTwo) >= 22 && intval($firstTwo) <= 27)) {
            return 'mastercard';
        }
        
        return null;
    }

    /**
     * Génère un CVV aléatoire
     */
    public function generateCVV(): string
    {
        return str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Génère une date d'expiration (3 ans dans le futur)
     */
    public function generateExpiryDate(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('+3 years');
    }
}
