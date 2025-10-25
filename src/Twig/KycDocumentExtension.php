<?php

namespace App\Twig;

use App\Entity\KycDocument;
use App\Service\ProfessionalTranslationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class KycDocumentExtension extends AbstractExtension
{
    public function __construct(
        private ProfessionalTranslationService $translationService
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('kyc_document_type_label', [$this, 'getTypeLabel']),
        ];
    }

    public function getTypeLabel(KycDocument $document): string
    {
        return $document->getTypeLabel($this->translationService);
    }
}
