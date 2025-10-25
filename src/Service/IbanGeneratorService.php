<?php

namespace App\Service;

use App\Entity\User;

/**
 * Service de génération d'IBAN valides avec de vrais codes bancaires
 * Génère des IBANs structurellement corrects qui ressemblent aux vrais IBANs
 */
class IbanGeneratorService
{
    /**
     * Génère un IBAN valide selon le pays de l'utilisateur
     */
    public function generateIbanForUser(User $user): string
    {
        $country = strtoupper($user->getCountry() ?? 'FR');
        
        return match($country) {
            'FR', 'FRANCE' => $this->generateFrenchIban(),
            'DE', 'GERMANY', 'ALLEMAGNE' => $this->generateGermanIban(),
            'BE', 'BELGIUM', 'BELGIQUE' => $this->generateBelgianIban(),
            'NL', 'NETHERLANDS', 'PAYS-BAS' => $this->generateDutchIban(),
            'ES', 'SPAIN', 'ESPAGNE' => $this->generateSpanishIban(),
            'IT', 'ITALY', 'ITALIE' => $this->generateItalianIban(),
            'LU', 'LUXEMBOURG' => $this->generateLuxembourgIban(),
            'GB', 'UK', 'UNITED KINGDOM', 'ROYAUME-UNI' => $this->generateBritishIban(),
            default => $this->generateFrenchIban(),
        };
    }

    /**
     * Génère un IBAN français avec de vrais codes bancaires
     * Format: FR + 2 contrôle + 5 banque + 5 guichet + 11 compte + 2 clé RIB
     */
    private function generateFrenchIban(): string
    {
        // Vrais codes bancaires français
        $realBankCodes = [
            '30004', // BNP Paribas
            '30002', // Crédit Lyonnais  
            '20041', // Banque Populaire
            '18206', // Crédit Mutuel
            '10278', // La Banque Postale
            '42559', // Crédit Coopératif
        ];
        
        $bankCode = $realBankCodes[array_rand($realBankCodes)];
        $branchCode = str_pad((string) random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
        $accountNumber = str_pad((string) random_int(10000000000, 99999999999), 11, '0', STR_PAD_LEFT);
        
        // Calculer la clé RIB française (modulo 97)
        $ribKey = $this->calculateFrenchRibKey($bankCode, $branchCode, $accountNumber);
        
        $bban = $bankCode . $branchCode . $accountNumber . $ribKey;
        
        return $this->createIban('FR', $bban);
    }

    /**
     * Génère un IBAN allemand avec de vrais codes bancaires (BLZ)
     * Format: DE + 2 contrôle + 8 BLZ + 10 compte
     */
    private function generateGermanIban(): string
    {
        // Vrais codes bancaires allemands (BLZ)
        $realBankCodes = [
            '37040044', // Commerzbank Köln
            '50010517', // Postbank Frankfurt
            '70070010', // Deutsche Bank München
            '60050101', // BW-Bank Stuttgart
            '12030000', // Deutsche Kreditbank Berlin
            '43060967', // GLS Bank Bochum
        ];
        
        $bankCode = $realBankCodes[array_rand($realBankCodes)];
        $accountNumber = str_pad((string) random_int(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
        
        $bban = $bankCode . $accountNumber;
        
        return $this->createIban('DE', $bban);
    }

    /**
     * Génère un IBAN belge avec de vrais codes bancaires
     * Format: BE + 2 contrôle + 3 banque + 7 compte + 2 contrôle
     */
    private function generateBelgianIban(): string
    {
        // Vrais codes bancaires belges
        $realBankCodes = [
            '001', // BNP Paribas Fortis
            '068', // Belfius Bank
            '310', // KBC Bank
            '979', // ING Belgium
            '523', // CBC Banque
        ];
        
        $bankCode = $realBankCodes[array_rand($realBankCodes)];
        $accountNumber = str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT);
        
        // Clé de contrôle belge (modulo 97)
        $accountForCheck = intval($bankCode . $accountNumber);
        $checkDigits = str_pad((string) ($accountForCheck % 97 ?: 97), 2, '0', STR_PAD_LEFT);
        
        $bban = $bankCode . $accountNumber . $checkDigits;
        
        return $this->createIban('BE', $bban);
    }

    /**
     * Génère un IBAN néerlandais avec de vrais codes bancaires
     * Format: NL + 2 contrôle + 4 banque + 10 compte
     */
    private function generateDutchIban(): string
    {
        // Vrais codes bancaires néerlandais
        $realBankCodes = [
            'RABO', // Rabobank
            'ABNA', // ABN AMRO
            'INGB', // ING Bank
            'TRIO', // Triodos Bank
            'ASNB', // ASN Bank
        ];
        
        $bankCode = $realBankCodes[array_rand($realBankCodes)];
        $accountNumber = str_pad((string) random_int(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
        
        $bban = $bankCode . $accountNumber;
        
        return $this->createIban('NL', $bban);
    }

    /**
     * Génère un IBAN espagnol avec de vrais codes bancaires
     * Format: ES + 2 contrôle + 4 banque + 4 agence + 2 DC + 10 compte
     */
    private function generateSpanishIban(): string
    {
        // Vrais codes bancaires espagnols
        $realBankCodes = [
            '0049', // Banco Santander
            '0081', // Banco Sabadell
            '0182', // BBVA
            '0128', // Bankinter
            '1465', // ING Direct España
        ];
        
        $bankCode = $realBankCodes[array_rand($realBankCodes)];
        $branchCode = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        $dc = '01'; // Clé de contrôle simplifiée
        $accountNumber = str_pad((string) random_int(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
        
        $bban = $bankCode . $branchCode . $dc . $accountNumber;
        
        return $this->createIban('ES', $bban);
    }

    /**
     * Génère un IBAN italien avec de vrais codes bancaires
     * Format: IT + 2 contrôle + 1 CIN + 5 ABI + 5 CAB + 12 compte
     */
    private function generateItalianIban(): string
    {
        $cinCode = 'X'; // Code CIN
        
        // Vrais codes bancaires italiens (ABI)
        $realBankCodes = [
            '01005', // Banca Nazionale del Lavoro
            '02008', // UniCredit
            '03069', // Intesa Sanpaolo
            '03111', // FinecoBank
            '03268', // Banco BPM
        ];
        
        $bankCode = $realBankCodes[array_rand($realBankCodes)];
        $branchCode = str_pad((string) random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
        $accountNumber = str_pad((string) random_int(100000000000, 999999999999), 12, '0', STR_PAD_LEFT);
        
        $bban = $cinCode . $bankCode . $branchCode . $accountNumber;
        
        return $this->createIban('IT', $bban);
    }

    /**
     * Génère un IBAN luxembourgeois
     * Format: LU + 2 contrôle + 3 banque + 13 compte
     */
    private function generateLuxembourgIban(): string
    {
        // Vrais codes bancaires luxembourgeois
        $realBankCodes = [
            '001', // Banque et Caisse d'Épargne de l'État
            '011', // BGL BNP Paribas
            '022', // Banque de Luxembourg
            '141', // Raiffeisen Luxembourg
        ];
        
        $bankCode = $realBankCodes[array_rand($realBankCodes)];
        $accountNumber = str_pad((string) random_int(1000000000000, 9999999999999), 13, '0', STR_PAD_LEFT);
        
        $bban = $bankCode . $accountNumber;
        
        return $this->createIban('LU', $bban);
    }

    /**
     * Génère un IBAN britannique
     * Format: GB + 2 contrôle + 4 banque + 6 sort code + 8 compte
     */
    private function generateBritishIban(): string
    {
        // Vrais codes bancaires britanniques
        $realBankCodes = [
            'ABBY', // Abbey National
            'BARC', // Barclays
            'HBUK', // HSBC UK
            'LOYD', // Lloyds
            'NWBK', // NatWest
        ];
        
        $bankCode = $realBankCodes[array_rand($realBankCodes)];
        $sortCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $accountNumber = str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        
        $bban = $bankCode . $sortCode . $accountNumber;
        
        return $this->createIban('GB', $bban);
    }

    /**
     * Calcule la clé RIB française
     */
    private function calculateFrenchRibKey(string $bankCode, string $branchCode, string $accountNumber): string
    {
        // Remplacer les lettres par des chiffres pour le calcul RIB
        $accountForRib = strtr($accountNumber, 
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '12345678912345678923456789'
        );
        
        $ribNumber = $bankCode . $branchCode . $accountForRib;
        $remainder = bcmod($ribNumber, '97');
        $ribKey = 97 - intval($remainder);
        
        return str_pad((string) $ribKey, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Crée un IBAN avec clé de contrôle calculée
     */
    private function createIban(string $countryCode, string $bban): string
    {
        // Calculer la clé de contrôle IBAN selon l'algorithme mod-97
        $tempIban = $bban . $countryCode . '00';
        
        // Convertir les lettres en chiffres (A=10, B=11, etc.)
        $numericString = '';
        for ($i = 0; $i < strlen($tempIban); $i++) {
            $char = $tempIban[$i];
            if (ctype_alpha($char)) {
                $numericString .= (ord(strtoupper($char)) - ord('A') + 10);
            } else {
                $numericString .= $char;
            }
        }
        
        // Calculer mod 97 avec bcmod pour les gros nombres
        $remainder = bcmod($numericString, '97');
        $checkDigits = 98 - intval($remainder);
        
        return $countryCode . str_pad((string) $checkDigits, 2, '0', STR_PAD_LEFT) . $bban;
    }

    /**
     * Formate un IBAN pour l'affichage avec des espaces
     */
    public function formatIbanForDisplay(string $iban): string
    {
        return trim(chunk_split($iban, 4, ' '));
    }

    /**
     * Valide un IBAN selon l'algorithme mod-97
     */
    public function validateIban(string $iban): bool
    {
        $iban = strtoupper(str_replace(' ', '', $iban));
        
        if (strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }
        
        // Réorganiser: déplacer les 4 premiers caractères à la fin
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);
        
        // Convertir les lettres en chiffres
        $numericString = '';
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $numericString .= (ord($char) - ord('A') + 10);
            } else {
                $numericString .= $char;
            }
        }
        
        // Le reste de la division par 97 doit être 1
        return bcmod($numericString, '97') === '1';
    }
}
