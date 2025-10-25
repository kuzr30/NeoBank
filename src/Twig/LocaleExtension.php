<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class LocaleExtension extends AbstractExtension
{
    private const AVAILABLE_LOCALES = ['fr', 'nl', 'de', 'en', 'es'];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('locale', [$this, 'getCurrentLocale']),
            new TwigFunction('locales', [$this, 'getAvailableLocales']),
            new TwigFunction('current_locale', [$this, 'getCurrentLocale']),
            new TwigFunction('localized_path', [$this, 'getLocalizedPath']),
            new TwigFunction('locale_path', [$this, 'getLocalizedPath']), // Alias
            // Fonction principale à utiliser au lieu de path()
            new TwigFunction('lpath', [$this, 'getLocalizedPath']), // Raccourci
        ];
    }

    public function getCurrentLocale(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request?->getLocale() ?? 'fr';
    }

    public function getAvailableLocales(): array
    {
        return self::AVAILABLE_LOCALES;
    }

    /**
     * Génère une URL avec la locale courante automatiquement incluse
     */
    public function getLocalizedPath(string $name, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        $currentLocale = $this->getCurrentLocale();
        
        // Ajouter la locale aux paramètres si elle n'est pas déjà présente
        if (!isset($parameters['_locale'])) {
            $parameters['_locale'] = $currentLocale;
        }

        return $this->urlGenerator->generate($name, $parameters, $referenceType);
    }
}
