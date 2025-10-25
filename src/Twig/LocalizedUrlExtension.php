<?php

namespace App\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class LocalizedUrlExtension extends AbstractExtension
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('localized_path', [$this, 'generateLocalizedPath']),
            new TwigFunction('localized_url', [$this, 'generateLocalizedUrl']),
        ];
    }

    /**
     * Génère un chemin localisé pour une route donnée
     */
    public function generateLocalizedPath(string $name, array $parameters = []): string
    {
        return $this->urlGenerator->generate($name, $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    /**
     * Génère une URL complète localisée pour une route donnée
     */
    public function generateLocalizedUrl(string $name, array $parameters = []): string
    {
        return $this->urlGenerator->generate($name, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
