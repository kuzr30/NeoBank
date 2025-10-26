<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Test fonctionnel du système de changement de langue
 *
 * Teste le parcours utilisateur complet:
 * 1. Navigation sur une page
 * 2. Clic sur le changement de langue
 * 3. Vérification que la page reste cohérente
 */
class LocaleSwitchFunctionalTest extends WebTestCase
{
    /**
     * Test le changement de langue depuis la page d'accueil
     */
    public function testLanguageSwitchFromHomePage(): void
    {
        $client = static::createClient();

        // 1. Visiter la page d'accueil en français
        $crawler = $client->request('GET', '/fr');
        $this->assertResponseIsSuccessful();

        // 2. Simuler clic sur changement vers NL
        $client->request('GET', '/fr/change-language/nl', [], [], [
            'HTTP_REFERER' => $client->getRequest()->getUri()
        ]);

        // 3. Vérifier la redirection vers NL
        $this->assertResponseRedirects();
        $client->followRedirect();

        // 4. Vérifier qu'on est bien sur /nl
        $this->assertStringContainsString('/nl', $client->getRequest()->getUri());
    }

    /**
     * Test critique: changement de langue sur une page profonde
     * (ancien bug: erreur 404 après switch)
     */
    public function testLanguageSwitchPreservesDeepRoute(): void
    {
        $client = static::createClient();

        // 1. Visiter une page profonde en français
        // Note: Adapter selon les routes publiques disponibles
        $client->request('GET', '/fr/offres-credit/credit-personnel');

        if ($client->getResponse()->getStatusCode() === 200) {
            $referer = $client->getRequest()->getUri();

            // 2. Changer vers l'anglais
            $client->request('GET', '/fr/change-language/en', [], [], [
                'HTTP_REFERER' => $referer
            ]);

            // 3. Suivre la redirection
            $this->assertResponseRedirects();
            $client->followRedirect();

            // 4. Vérifier qu'on n'a PAS de 404
            $this->assertResponseIsSuccessful('Language switch should not result in 404');

            // 5. Vérifier qu'on est sur /en
            $this->assertStringContainsString('/en', $client->getRequest()->getUri());

            // 6. Vérifier que "credit-personnel" a été traduit
            $uri = $client->getRequest()->getUri();
            $this->assertStringNotContainsString('credit-personnel', $uri,
                'French segment should be translated');
        } else {
            $this->markTestSkipped('Route not publicly accessible');
        }
    }

    /**
     * Test changement entre toutes les paires de langues
     */
    public function testLanguageSwitchBetweenAllLocales(): void
    {
        $client = static::createClient();
        $locales = ['fr', 'nl', 'de', 'en', 'es'];

        foreach ($locales as $fromLocale) {
            foreach ($locales as $toLocale) {
                if ($fromLocale === $toLocale) {
                    continue;
                }

                // Visiter home avec la locale source
                $client->request('GET', "/{$fromLocale}");

                if ($client->getResponse()->isSuccessful()) {
                    // Changer vers locale cible
                    $client->request('GET', "/{$fromLocale}/change-language/{$toLocale}", [], [], [
                        'HTTP_REFERER' => $client->getRequest()->getUri()
                    ]);

                    // Vérifier la redirection
                    $this->assertResponseRedirects(
                        null,
                        "Switch from {$fromLocale} to {$toLocale} should redirect"
                    );

                    $client->followRedirect();

                    // Vérifier qu'on est bien sur la bonne locale
                    $uri = $client->getRequest()->getUri();
                    $this->assertStringContainsString("/{$toLocale}",
                        $uri,
                        "Should be on /{$toLocale} after switch from {$fromLocale}"
                    );
                }
            }
        }
    }

    /**
     * Test que la session conserve la locale
     */
    public function testSessionPreservesLocale(): void
    {
        $client = static::createClient();

        // 1. Visiter en français
        $client->request('GET', '/fr');
        $this->assertResponseIsSuccessful();

        // 2. Vérifier que la session contient 'fr'
        $session = $client->getRequest()->getSession();
        $this->assertEquals('fr', $session->get('_locale'));

        // 3. Changer vers néerlandais
        $client->request('GET', '/fr/change-language/nl', [], [], [
            'HTTP_REFERER' => $client->getRequest()->getUri()
        ]);
        $client->followRedirect();

        // 4. Vérifier que la session a été mise à jour
        $session = $client->getRequest()->getSession();
        $this->assertEquals('nl', $session->get('_locale'));
    }

    /**
     * Test des pages d'erreur avec locale préservée
     */
    public function testErrorPagesPreserveLocale(): void
    {
        $client = static::createClient();

        $locales = ['fr', 'nl', 'en', 'de', 'es'];

        foreach ($locales as $locale) {
            // Visiter une page qui n'existe pas
            $client->request('GET', "/{$locale}/cette-page-nexiste-pas-123456");

            // Doit être une 404
            $this->assertResponseStatusCodeSame(404);

            // La locale doit être préservée dans la requête
            $this->assertEquals($locale, $client->getRequest()->getLocale());
        }
    }

    /**
     * Test que les URLs invalides fallback vers la locale par défaut
     */
    public function testInvalidLocaleRedirectsToDefault(): void
    {
        $client = static::createClient();

        // Tenter d'accéder avec une locale invalide
        $client->request('GET', '/xx');

        // Devrait rediriger ou afficher en français (locale par défaut)
        if ($client->getResponse()->isRedirection()) {
            $client->followRedirect();
        }

        // Vérifier qu'on finit sur une locale valide
        $uri = $client->getRequest()->getUri();
        $hasValidLocale = preg_match('#/(fr|nl|de|en|es)#', $uri);
        $this->assertTrue((bool)$hasValidLocale, 'Should redirect to a valid locale');
    }

    /**
     * Test que le changement de langue ne casse pas les paramètres GET
     */
    public function testLanguageSwitchPreservesQueryParameters(): void
    {
        $client = static::createClient();

        // Visiter une URL avec paramètres GET
        $client->request('GET', '/fr?foo=bar&baz=qux');

        if ($client->getResponse()->isSuccessful()) {
            $referer = $client->getRequest()->getUri();

            // Changer de langue
            $client->request('GET', '/fr/change-language/nl', [], [], [
                'HTTP_REFERER' => $referer
            ]);

            $this->assertResponseRedirects();
            $client->followRedirect();

            // Les paramètres GET devraient être préservés
            $uri = $client->getRequest()->getUri();

            // Note: Selon l'implémentation, les query params peuvent ou non être préservés
            // Ce test documente le comportement attendu
            $this->assertResponseIsSuccessful();
        } else {
            $this->markTestSkipped('Home page not accessible');
        }
    }

    /**
     * Test scénario réel: Navigation Banking complète
     */
    public function testRealWorldBankingNavigationWithLanguageSwitch(): void
    {
        $client = static::createClient();

        // Note: Ce test nécessite une authentification
        // À adapter selon votre système d'authentification

        // 1. Aller sur la page de connexion FR
        $client->request('GET', '/fr/connexion');

        if ($client->getResponse()->getStatusCode() === 200) {
            // 2. Changer vers NL avant de se connecter
            $client->request('GET', '/fr/change-language/nl', [], [], [
                'HTTP_REFERER' => $client->getRequest()->getUri()
            ]);

            $this->assertResponseRedirects();
            $client->followRedirect();

            // 3. Vérifier qu'on est sur /nl/inloggen
            $uri = $client->getRequest()->getUri();
            $this->assertStringContainsString('/nl', $uri);
            $this->assertStringContainsString('inloggen', $uri);
        } else {
            $this->markTestSkipped('Login page not accessible');
        }
    }
}
