<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SeoService
{
    private RequestStack $requestStack;
    private RouterInterface $router;
    private TranslatorInterface $translator;
    private array $defaultMeta;
    private array $organizationData;

    public function __construct(
        RequestStack $requestStack,
        RouterInterface $router,
        TranslatorInterface $translator,
        private string $siteDomain,
        private string $siteName,
        private string $defaultSeoTitle,
        private string $defaultSeoDescription,
        private string $defaultSeoKeywords,
        private string $twitterHandle,
        private string $companyEmail
    ) {
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->translator = $translator;
        
        $this->initializeDefaults();
    }

    private function initializeDefaults(): void
    {
        $this->defaultMeta = [
            'title' => $this->defaultSeoTitle,
            'description' => $this->defaultSeoDescription,
            'keywords' => $this->defaultSeoKeywords,
            'author' => $this->siteName,
            'publisher' => $this->siteName,
            'robots' => 'index, follow',
            'language' => 'fr',
            'type' => 'website'
        ];

        $this->organizationData = [
            '@context' => 'https://schema.org',
            '@type' => 'FinancialService',
            'name' => $this->siteName,
            'alternateName' => 'SEDEF',
            'url' => $this->siteDomain,
            'logo' => $this->siteDomain . '/logo.png',
            'image' => $this->siteDomain . '/logo.png',
            'description' => $this->defaultSeoDescription,
            'founder' => [
                '@type' => 'Person',
                'name' => $this->siteName
            ],
            'foundingDate' => '2020',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $_ENV['COMPANY_ADDRESS_STREET'] ?? '',
                'addressLocality' => $_ENV['COMPANY_ADDRESS_CITY'] ?? 'France',
                'postalCode' => $_ENV['COMPANY_ADDRESS_POSTAL_CODE'] ?? '',
                'addressCountry' => 'FR'
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => $_ENV['COMPANY_PHONE'] ?? '',
                'email' => $this->companyEmail,
                'contactType' => 'customer service',
                'availableLanguage' => ['French', 'English', 'Dutch', 'German', 'Spanish']
            ],
            'sameAs' => array_filter([
                $_ENV['SOCIAL_FACEBOOK_URL'] ?? null,
                $_ENV['SOCIAL_TWITTER_URL'] ?? null,
                $_ENV['SOCIAL_LINKEDIN_URL'] ?? null,
                $_ENV['SOCIAL_INSTAGRAM_URL'] ?? null,
            ]),
            'hasOfferCatalog' => [
                '@type' => 'OfferCatalog',
                'name' => 'Services Bancaires',
                'itemListElement' => [
                    [
                        '@type' => 'Offer',
                        'itemOffered' => [
                            '@type' => 'FinancialProduct',
                            'name' => 'Crédit Immobilier',
                            'category' => 'Mortgage'
                        ]
                    ],
                    [
                        '@type' => 'Offer',
                        'itemOffered' => [
                            '@type' => 'FinancialProduct',
                            'name' => 'Assurance',
                            'category' => 'Insurance'
                        ]
                    ],
                    [
                        '@type' => 'Offer',
                        'itemOffered' => [
                            '@type' => 'FinancialProduct',
                            'name' => 'Compte Bancaire',
                            'category' => 'Banking'
                        ]
                    ]
                ]
            ]
        ];
    }

    public function getMetaTags(array $customMeta = []): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $locale = $request ? $request->getLocale() : 'fr';
        
        $meta = array_merge($this->defaultMeta, $customMeta);
        
        // Générer l'URL canonique
        if ($request) {
            $meta['canonical'] = $this->generateCanonicalUrl($request);
            $meta['hreflang'] = $this->generateHreflangUrls($request);
        }
        
        return $meta;
    }

    public function getJsonLdData(array $customData = []): array
    {
        return array_merge($this->organizationData, $customData);
    }

    public function getBreadcrumbJsonLd(array $breadcrumbs): array
    {
        $items = [];
        foreach ($breadcrumbs as $index => $breadcrumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $breadcrumb['name'],
                'item' => $breadcrumb['url']
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        ];
    }

    public function getArticleJsonLd(array $articleData): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $articleData['title'],
            'description' => $articleData['description'],
            'image' => $articleData['image'] ?? 'https://sedef.fr/logo.png',
            'author' => [
                '@type' => 'Organization',
                'name' => 'SEDEF BANK'
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'SEDEF BANK',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => 'https://sedef.fr/logo.png'
                ]
            ],
            'datePublished' => $articleData['datePublished'] ?? date('c'),
            'dateModified' => $articleData['dateModified'] ?? date('c'),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $articleData['url']
            ]
        ];
    }

    public function getServiceJsonLd(array $serviceData): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $serviceData['name'],
            'description' => $serviceData['description'],
            'provider' => [
                '@type' => 'Organization',
                'name' => 'SEDEF BANK'
            ],
            'areaServed' => [
                '@type' => 'Country',
                'name' => 'France'
            ],
            'hasOfferCatalog' => [
                '@type' => 'OfferCatalog',
                'name' => $serviceData['name'],
                'itemListElement' => $serviceData['offers'] ?? []
            ]
        ];
    }

    private function generateCanonicalUrl(Request $request): string
    {
        $baseUrl = $request->getSchemeAndHttpHost();
        $pathInfo = $request->getPathInfo();
        
        return $baseUrl . $pathInfo;
    }

    private function generateHreflangUrls(Request $request): array
    {
        $hreflangUrls = [];
        $currentRoute = $request->attributes->get('_route');
        $routeParams = $request->attributes->get('_route_params', []);
        
        $supportedLocales = ['fr', 'en', 'nl', 'de', 'es'];
        
        foreach ($supportedLocales as $locale) {
            try {
                $url = $this->router->generate($currentRoute, array_merge($routeParams, ['_locale' => $locale]));
                $hreflangUrls[$locale] = $request->getSchemeAndHttpHost() . $url;
            } catch (\Exception $e) {
                // Route non disponible pour cette locale
            }
        }
        
        return $hreflangUrls;
    }

    public function getPageTypeJsonLd(string $pageType, array $data = []): array
    {
        switch ($pageType) {
            case 'home':
                return $this->getHomepageJsonLd();
            case 'contact':
                return $this->getContactPageJsonLd();
            case 'about':
                return $this->getAboutPageJsonLd();
            case 'service':
                return $this->getServiceJsonLd($data);
            case 'article':
                return $this->getArticleJsonLd($data);
            default:
                return $this->getJsonLdData();
        }
    }

    private function getHomepageJsonLd(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'SEDEF BANK',
            'url' => 'https://sedef.fr',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => 'https://sedef.fr/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string'
            ],
            'publisher' => $this->organizationData
        ];
    }

    private function getContactPageJsonLd(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ContactPage',
            'name' => 'Contact - SEDEF BANK',
            'description' => 'Contactez SEDEF BANK pour tous vos besoins bancaires et financiers',
            'mainEntity' => $this->organizationData
        ];
    }

    private function getAboutPageJsonLd(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'AboutPage',
            'name' => 'À propos - SEDEF BANK',
            'description' => 'Découvrez SEDEF BANK, ses services et son engagement envers ses clients',
            'mainEntity' => $this->organizationData
        ];
    }
}