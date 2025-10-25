<?php

namespace App\EventListener;

use App\Service\LocalizedRoutingService;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsEventListener(event: SitemapPopulateEvent::class)]
class SitemapListener
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private LocalizedRoutingService $localizedRoutingService
    ) {}

    public function __invoke(SitemapPopulateEvent $event): void
    {
        $this->registerStaticPages($event);
        $this->registerServicePages($event);
        $this->registerLegalPages($event);
    }

    private function registerStaticPages(SitemapPopulateEvent $event): void
    {
        // Page d'accueil principale
        $staticUrls = [
            'https://sedef.fr/' => ['priority' => 1.0, 'changefreq' => 'daily'],
        ];

        // Pages d'accueil par langue
        $languages = ['fr', 'en', 'nl', 'de', 'es'];
        foreach ($languages as $locale) {
            $staticUrls["https://sedef.fr/{$locale}"] = [
                'priority' => $locale === 'fr' ? 1.0 : 0.9, 
                'changefreq' => 'daily'
            ];
        }

        // Pages principales multilingues avec traductions
        $mainRoutes = [
            'support_contact' => ['priority' => 0.7, 'changefreq' => 'weekly'],
        ];

        foreach ($languages as $locale) {
            foreach ($mainRoutes as $routeKey => $config) {
                $localizedPath = $this->localizedRoutingService->getLocalizedPath($routeKey, $locale);
                $staticUrls["https://sedef.fr/{$locale}/{$localizedPath}"] = $config;
            }
        }

        foreach ($staticUrls as $url => $config) {
            $urlConcrete = new UrlConcrete(
                $url,
                new \DateTime(),
                $config['changefreq'],
                $config['priority']
            );
            
            $event->getUrlContainer()->addUrl($urlConcrete, 'default');
        }
    }

    private function registerServicePages(SitemapPopulateEvent $event): void
    {
        // Services multilingues avec traductions
        $languages = ['fr', 'en', 'nl', 'de', 'es'];
        $serviceRoutes = [
            'credit_simulation_index' => ['priority' => 0.8, 'changefreq' => 'weekly'],
            'services_accounts_cards' => ['priority' => 0.8, 'changefreq' => 'weekly'], 
            'services_savings_investments' => ['priority' => 0.8, 'changefreq' => 'weekly'],
            'services_insurances' => ['priority' => 0.8, 'changefreq' => 'weekly'],
            'credit_offers_index' => ['priority' => 0.8, 'changefreq' => 'weekly'],
            'banking_mobile_app' => ['priority' => 0.7, 'changefreq' => 'weekly'],
            'banking_credit_card' => ['priority' => 0.7, 'changefreq' => 'weekly'],
        ];

        foreach ($languages as $locale) {
            foreach ($serviceRoutes as $routeKey => $config) {
                $localizedPath = $this->localizedRoutingService->getLocalizedPath($routeKey, $locale);
                $url = "https://sedef.fr/{$locale}/{$localizedPath}";
                $urlConcrete = new UrlConcrete(
                    $url,
                    new \DateTime(),
                    $config['changefreq'],
                    $config['priority']
                );
                
                $event->getUrlContainer()->addUrl($urlConcrete, 'services');
            }
        }
    }

    private function registerLegalPages(SitemapPopulateEvent $event): void
    {
        // Pages légales multilingues avec traductions
        $languages = ['fr', 'en', 'nl', 'de', 'es'];
        $legalRoutes = [
            'legal_notices' => ['priority' => 0.3, 'changefreq' => 'yearly'],
            'legal_privacy' => ['priority' => 0.4, 'changefreq' => 'yearly'],
            'legal_terms' => ['priority' => 0.4, 'changefreq' => 'yearly'],
            'legal_cookies' => ['priority' => 0.3, 'changefreq' => 'yearly'],
        ];

        foreach ($languages as $locale) {
            foreach ($legalRoutes as $routeKey => $config) {
                $localizedPath = $this->localizedRoutingService->getLocalizedPath($routeKey, $locale);
                $url = "https://sedef.fr/{$locale}/{$localizedPath}";
                $urlConcrete = new UrlConcrete(
                    $url,
                    new \DateTime(),
                    $config['changefreq'],
                    $config['priority']
                );
                
                $event->getUrlContainer()->addUrl($urlConcrete, 'legal');
            }
        }
    }
}