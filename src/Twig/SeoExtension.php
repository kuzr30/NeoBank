<?php

namespace App\Twig;

use App\Service\SeoService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SeoExtension extends AbstractExtension
{
    private SeoService $seoService;

    public function __construct(SeoService $seoService)
    {
        $this->seoService = $seoService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('seo_meta', [$this, 'getSeoMeta']),
            new TwigFunction('seo_json_ld', [$this, 'getSeoJsonLd']),
            new TwigFunction('seo_breadcrumb', [$this, 'getBreadcrumbJsonLd']),
            new TwigFunction('seo_page_type', [$this, 'getPageTypeJsonLd']),
        ];
    }

    public function getSeoMeta(array $customMeta = []): array
    {
        return $this->seoService->getMetaTags($customMeta);
    }

    public function getSeoJsonLd(array $customData = []): array
    {
        return $this->seoService->getJsonLdData($customData);
    }

    public function getBreadcrumbJsonLd(array $breadcrumbs): array
    {
        return $this->seoService->getBreadcrumbJsonLd($breadcrumbs);
    }

    public function getPageTypeJsonLd(string $pageType, array $data = []): array
    {
        return $this->seoService->getPageTypeJsonLd($pageType, $data);
    }
}