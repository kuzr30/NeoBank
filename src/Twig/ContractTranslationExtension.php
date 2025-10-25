<?php

namespace App\Twig;

use App\Service\ProfessionalTranslationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension Twig spécialement conçue pour les traductions de contrats PDF
 */
class ContractTranslationExtension extends AbstractExtension
{
    public function __construct(
        private ProfessionalTranslationService $translationService
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('contract_trans', [$this, 'translateContract']),
            new TwigFunction('ct', [$this, 'translateContract']), // Raccourci
        ];
    }

    /**
     * Fonction simplifiée pour traduire les clés du contrat
     * Usage: {{ contract_trans('credit_contract.title', {'%reference_number%': creditApplication.referenceNumber}) }}
     * Usage raccourci: {{ ct('credit_contract.title', {'%reference_number%': creditApplication.referenceNumber}) }}
     */
    public function translateContract(string $key, array $parameters = [], ?string $locale = null): string
    {
        // Si une locale spécifique est fournie, l'utiliser temporairement
        if ($locale) {
            $originalLocale = $this->translationService->getLocale();
            $this->translationService->setLocale($locale);
            
            $translation = $this->translationService->trans($key, $parameters, 'credit_contract');
            
            // Restaurer la locale originale
            $this->translationService->setLocale($originalLocale);
            
            return $translation;
        }
        
        // Sinon utiliser la locale actuelle
        return $this->translationService->trans($key, $parameters, 'credit_contract');
    }
}