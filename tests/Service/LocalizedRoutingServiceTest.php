<?php

namespace App\Tests\Service;

use App\Service\LocalizedRoutingService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests de régression pour LocalizedRoutingService
 *
 * Ces tests garantissent que:
 * - Le changement de langue fonctionne correctement
 * - Les routes sont bien traduites
 * - Pas de bugs 404 lors du switch de langue
 */
class LocalizedRoutingServiceTest extends KernelTestCase
{
    private LocalizedRoutingService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->service = $container->get(LocalizedRoutingService::class);
    }

    /**
     * Test que switchLocaleInUrl traduit correctement les URLs simples
     */
    public function testSwitchLocaleInUrlSimpleRoute(): void
    {
        // FR → NL
        $result = $this->service->switchLocaleInUrl('/fr', 'nl');
        $this->assertStringContainsString('/nl', $result);

        // NL → EN
        $result = $this->service->switchLocaleInUrl('/nl', 'en');
        $this->assertStringContainsString('/en', $result);
    }

    /**
     * Test critique: Banking virements (bug le plus fréquent avant refactoring)
     */
    public function testSwitchLocaleInUrlBankingTransfers(): void
    {
        // FR → NL: /fr/banking/virements/nouveau → /nl/banking/overboekingen/nieuw
        $result = $this->service->switchLocaleInUrl('/fr/banking/virements/nouveau', 'nl');

        $this->assertStringContainsString('/nl', $result, 'Locale should be NL');
        $this->assertStringContainsString('banking', $result, 'Should contain banking segment');

        // Vérifier qu'on n'a pas gardé "virements" en français
        $this->assertStringNotContainsString('virements', $result, 'Should not contain French word');
    }

    /**
     * Test avec paramètres dynamiques (IDs)
     */
    public function testSwitchLocaleInUrlWithDynamicParameters(): void
    {
        // URL avec ID: /fr/banking/virements/123/details
        $result = $this->service->switchLocaleInUrl('/fr/banking/virements/123/details', 'en');

        $this->assertStringContainsString('/en', $result);
        $this->assertStringContainsString('123', $result, 'ID parameter should be preserved');
    }

    /**
     * Test de getLocalizedPath
     */
    public function testGetLocalizedPath(): void
    {
        // Test pour chaque langue
        $this->assertEquals('ma-demande-de-credit',
            $this->service->getLocalizedPath('credit_application_start', 'fr'));

        $this->assertEquals('mijn-kredietaanvraag',
            $this->service->getLocalizedPath('credit_application_start', 'nl'));

        $this->assertEquals('my-credit-application',
            $this->service->getLocalizedPath('credit_application_start', 'en'));

        $this->assertEquals('mein-kreditantrag',
            $this->service->getLocalizedPath('credit_application_start', 'de'));

        $this->assertEquals('mi-solicitud-credito',
            $this->service->getLocalizedPath('credit_application_start', 'es'));
    }

    /**
     * Test de getRouteKeyFromPath (inverse)
     */
    public function testGetRouteKeyFromPath(): void
    {
        $routeKey = $this->service->getRouteKeyFromPath('ma-demande-de-credit', 'fr');
        $this->assertEquals('credit_application_start', $routeKey);

        $routeKey = $this->service->getRouteKeyFromPath('mijn-kredietaanvraag', 'nl');
        $this->assertEquals('credit_application_start', $routeKey);
    }

    /**
     * Test que les locales non supportées fallback vers FR
     */
    public function testInvalidLocaleFallbacksToDefault(): void
    {
        $result = $this->service->switchLocaleInUrl('/fr/banking/virements', 'xx');
        $this->assertStringContainsString('/fr', $result, 'Invalid locale should fallback to FR');
    }

    /**
     * Test que getSupportedLocales retourne les 5 langues
     */
    public function testGetSupportedLocales(): void
    {
        $locales = $this->service->getSupportedLocales();

        $this->assertCount(5, $locales);
        $this->assertContains('fr', $locales);
        $this->assertContains('nl', $locales);
        $this->assertContains('de', $locales);
        $this->assertContains('en', $locales);
        $this->assertContains('es', $locales);
    }

    /**
     * Test generateLocalizedUrl
     */
    public function testGenerateLocalizedUrl(): void
    {
        $url = $this->service->generateLocalizedUrl('home_index', [], 'fr');
        $this->assertStringContainsString('/fr', $url);

        $url = $this->service->generateLocalizedUrl('home_index', [], 'nl');
        $this->assertStringContainsString('/nl', $url);
    }

    /**
     * Test de warmup (préchargement)
     */
    public function testWarmup(): void
    {
        // Ne doit pas lever d'exception
        $this->service->warmup();

        // Après warmup, les paths doivent être accessibles instantanément
        $path = $this->service->getLocalizedPath('credit_application_start', 'fr');
        $this->assertNotEmpty($path);
    }

    /**
     * Test clearCache
     */
    public function testClearCache(): void
    {
        // Charger des données
        $this->service->getLocalizedPath('home_index', 'fr');

        // Nettoyer le cache
        $this->service->clearCache();

        // Doit recharger les données
        $path = $this->service->getLocalizedPath('home_index', 'fr');
        $this->assertNotNull($path);
    }

    /**
     * Test que tous les segments importants sont traduits
     */
    public function testImportantSegmentsAreTranslated(): void
    {
        $importantSegments = [
            'comptes' => ['nl', 'en', 'de', 'es'],
            'cartes' => ['nl', 'en', 'de', 'es'],
            'virements' => ['nl', 'en', 'de', 'es'],
            'credits' => ['nl', 'en', 'de', 'es'],
        ];

        foreach ($importantSegments as $segment => $targetLocales) {
            foreach ($targetLocales as $locale) {
                $url = "/fr/banking/{$segment}/nouveau";
                $result = $this->service->switchLocaleInUrl($url, $locale);

                // Ne doit pas contenir le segment français
                $this->assertStringNotContainsString($segment, $result,
                    "Segment '{$segment}' should be translated to {$locale}");
            }
        }
    }
}
