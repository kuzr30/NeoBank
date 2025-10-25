<?php

namespace App\Validator;

use App\Service\ProfessionalTranslationService;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validateur personnalisé qui utilise notre service de traduction
 */
abstract class AbstractTranslatedValidator extends ConstraintValidator
{
    public function __construct(
        protected ProfessionalTranslationService $translationService
    ) {}

    /**
     * Traduit un message d'erreur en utilisant notre domaine de traduction
     */
    protected function translateMessage(string $key, array $parameters = []): string
    {
        return $this->translationService->tp($key, $parameters, 'credit_step_forms');
    }
}
