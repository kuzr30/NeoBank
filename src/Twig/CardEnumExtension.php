<?php

namespace App\Twig;

use App\Enum\CardConditionEnum;
use App\Service\ProfessionalTranslationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension Twig pour les fonctions liées aux cartes bancaires
 */
class CardEnumExtension extends AbstractExtension
{
    public function __construct(
        private ProfessionalTranslationService $translationService
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_card_conditions', [$this, 'getCardConditions']),
            new TwigFunction('get_card_label', [$this, 'getCardLabel']),
            new TwigFunction('get_card_description', [$this, 'getCardDescription']),
            new TwigFunction('get_card_features', [$this, 'getCardFeatures']),
        ];
    }

    /**
     * Récupère les conditions d'une carte traduite
     */
    public function getCardConditions(CardConditionEnum $cardType, string $locale = 'fr'): array
    {
        $key = match($cardType) {
            CardConditionEnum::CLASSIC => 'conditions.classic',
            CardConditionEnum::GOLD => 'conditions.gold',
            CardConditionEnum::PLATINUM => 'conditions.platinum'
        };

        return $this->translationService->getTranslationArray("banking_cards_tab.{$key}", $locale);
    }

    /**
     * Récupère le label d'une carte traduit
     */
    public function getCardLabel(CardConditionEnum $cardType, string $locale = 'fr'): string
    {
        $key = match($cardType) {
            CardConditionEnum::CLASSIC => 'card_types.classic.label',
            CardConditionEnum::GOLD => 'card_types.gold.label',
            CardConditionEnum::PLATINUM => 'card_types.platinum.label'
        };

        return $this->translationService->translate("banking_cards_tab.{$key}", [], 'banking_cards_tab', $locale);
    }

    /**
     * Récupère la description d'une carte traduite
     */
    public function getCardDescription(CardConditionEnum $cardType, string $locale = 'fr'): string
    {
        $key = match($cardType) {
            CardConditionEnum::CLASSIC => 'card_types.classic.description',
            CardConditionEnum::GOLD => 'card_types.gold.description',
            CardConditionEnum::PLATINUM => 'card_types.platinum.description'
        };

        return $this->translationService->translate("banking_cards_tab.{$key}", [], 'banking_cards_tab', $locale);
    }

    /**
     * Récupère les caractéristiques d'une carte traduites
     */
    public function getCardFeatures(CardConditionEnum $cardType, string $locale = 'fr'): array
    {
        $key = match($cardType) {
            CardConditionEnum::CLASSIC => 'card_types.classic.features',
            CardConditionEnum::GOLD => 'card_types.gold.features',
            CardConditionEnum::PLATINUM => 'card_types.platinum.features'
        };

        return $this->translationService->getTranslationArray("banking_cards_tab.{$key}", $locale);
    }
}