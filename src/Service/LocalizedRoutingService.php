<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Yaml\Yaml;

/**
 * Service professionnel pour la gestion des routes localisées
 *
 * Remplace l'ancienne approche avec 1500 lignes de hardcoded arrays
 * par un système moderne basé sur fichiers YAML externalisés
 *
 * @author Refactored by Claude - Professional Architecture
 */
final class LocalizedRoutingService
{
    private const SUPPORTED_LOCALES = ['fr', 'nl', 'de', 'en', 'es'];
    private const DEFAULT_LOCALE = 'fr';

    /** Cache des traductions de routes par locale */
    private array $routeCache = [];

    /** Cache des traductions de segments par locale */
    private array $segmentCache = [];

    private string $currentLocale;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        #[Autowire('%kernel.default_locale%')] private readonly string $defaultLocale,
        private readonly RequestStack $requestStack,
        private readonly RouterInterface $router
    ) {
        $this->currentLocale = $this->getCurrentLocaleFromRequest() ?? $this->defaultLocale;
    }

    /**
     * Récupère la locale actuelle depuis la requête
     */
    private function getCurrentLocaleFromRequest(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        $locale = $request?->getLocale();

        return $this->isValidLocale($locale) ? $locale : null;
    }

    /**
     * Valide qu'une locale est supportée
     */
    private function isValidLocale(?string $locale): bool
    {
        return $locale && in_array($locale, self::SUPPORTED_LOCALES, true);
    }

    /**
     * Charge les traductions de routes pour une locale donnée depuis YAML
     */
    private function loadRouteTranslations(string $locale): void
    {
        if (isset($this->routeCache[$locale])) {
            return; // Déjà chargé
        }

        $filePath = $this->projectDir . "/translations/routes.{$locale}.yaml";

        if (!file_exists($filePath)) {
            // Fallback vers la locale par défaut
            $filePath = $this->projectDir . "/translations/routes.{$this->defaultLocale}.yaml";
        }

        try {
            $data = Yaml::parseFile($filePath);
            $this->routeCache[$locale] = $data['routes'] ?? [];
            $this->segmentCache[$locale] = $data['segments'] ?? [];
        } catch (\Exception $e) {
            error_log("Failed to load route translations for locale '{$locale}': " . $e->getMessage());
            $this->routeCache[$locale] = [];
            $this->segmentCache[$locale] = [];
        }
    }

    /**
     * Récupère le chemin localisé pour un nom de route donné
     *
     * @param string $routeName Nom de la route (ex: 'credit_application_start')
     * @param string $locale Locale cible (ex: 'fr', 'nl')
     * @return string Chemin localisé (ex: 'ma-demande-de-credit')
     */
    public function getLocalizedPath(string $routeName, string $locale): string
    {
        if (!$this->isValidLocale($locale)) {
            $locale = $this->defaultLocale;
        }

        $this->loadRouteTranslations($locale);

        return $this->routeCache[$locale][$routeName] ?? $routeName;
    }

    /**
     * Trouve le nom de route à partir d'un chemin localisé
     *
     * @param string $path Chemin localisé (ex: 'mijn-kredietaanvraag')
     * @param string $locale Locale du chemin
     * @return string|null Nom de la route ou null si non trouvé
     */
    public function getRouteKeyFromPath(string $path, string $locale): ?string
    {
        if (!$this->isValidLocale($locale)) {
            return null;
        }

        $this->loadRouteTranslations($locale);

        // Recherche inversée dans le cache
        $routes = $this->routeCache[$locale] ?? [];
        $routeKey = array_search($path, $routes, true);

        return $routeKey !== false ? $routeKey : null;
    }

    /**
     * Change la locale dans une URL en préservant la route et les paramètres
     *
     * NOUVELLE IMPLÉMENTATION SIMPLIFIÉE (réduit de 93 lignes à 30)
     * Utilise le Router Symfony au lieu de parsing manuel
     *
     * @param string $url URL complète ou path (ex: '/fr/banking/virements/nouveau')
     * @param string $newLocale Nouvelle locale cible (ex: 'nl')
     * @return string URL avec la nouvelle locale (ex: '/nl/banking/overboekingen/nieuw')
     */
    public function switchLocaleInUrl(string $url, string $newLocale): string
    {
        if (!$this->isValidLocale($newLocale)) {
            $newLocale = $this->defaultLocale;
        }

        // Extraire le path de l'URL
        $pathInfo = parse_url($url, PHP_URL_PATH) ?? $url;

        // Nettoyer le path (enlever double slashes, etc.)
        $pathInfo = preg_replace('#/+#', '/', trim($pathInfo, '/'));
        $pathInfo = '/' . $pathInfo;

        try {
            // Essayer de matcher la route actuelle avec le Router Symfony
            $params = $this->router->match($pathInfo);
            $routeName = $params['_route'] ?? null;

            if (!$routeName) {
                throw new RouteNotFoundException('No route matched');
            }

            // Nettoyer les paramètres internes Symfony
            unset($params['_route'], $params['_controller'], $params['_locale']);

            // Ajouter la nouvelle locale
            $params['_locale'] = $newLocale;

            // Régénérer l'URL avec le Router
            return $this->router->generate($routeName, $params);

        } catch (RouteNotFoundException | \Exception $e) {
            // Fallback: Si le router ne trouve pas la route, essayer la traduction manuelle
            return $this->fallbackSwitchLocale($pathInfo, $newLocale);
        }
    }

    /**
     * Méthode de fallback pour le changement de locale si le Router échoue
     * Utilisée uniquement pour les cas edge où le Router ne peut pas matcher
     */
    private function fallbackSwitchLocale(string $pathInfo, string $newLocale): string
    {
        // Extraire la locale actuelle du path
        if (preg_match('#^/([a-z]{2})(/.*)?$#', $pathInfo, $matches)) {
            $currentLocale = $matches[1];
            $remainingPath = $matches[2] ?? '';

            if (!$this->isValidLocale($currentLocale)) {
                // Si pas de locale valide, rediriger vers home
                return "/{$newLocale}";
            }

            // Charger les segments pour les deux locales
            $this->loadRouteTranslations($currentLocale);
            $this->loadRouteTranslations($newLocale);

            // Traduire les segments du path
            $translatedPath = $this->translatePathSegments($remainingPath, $currentLocale, $newLocale);

            return "/{$newLocale}{$translatedPath}";
        }

        // Si aucun pattern ne match, aller à l'accueil
        return "/{$newLocale}";
    }

    /**
     * Traduit les segments d'un chemin d'une locale à une autre
     *
     * @param string $path Chemin à traduire (ex: '/banking/virements/nouveau')
     * @param string $fromLocale Locale source
     * @param string $toLocale Locale cible
     * @return string Chemin traduit (ex: '/banking/overboekingen/nieuw')
     */
    private function translatePathSegments(string $path, string $fromLocale, string $toLocale): string
    {
        if (empty($path) || $path === '/') {
            return '';
        }

        $segments = explode('/', trim($path, '/'));
        $translatedSegments = [];

        foreach ($segments as $segment) {
            // Si c'est un paramètre dynamique (commence par { ou est numérique), le garder tel quel
            if (empty($segment) || str_starts_with($segment, '{') || is_numeric($segment)) {
                $translatedSegments[] = $segment;
                continue;
            }

            // Chercher la traduction du segment
            $sourceSegments = $this->segmentCache[$fromLocale] ?? [];
            $targetSegments = $this->segmentCache[$toLocale] ?? [];

            // Vérifier si le segment existe dans la locale source
            if (isset($sourceSegments[$segment]) && is_array($sourceSegments[$segment])) {
                // C'est un segment avec traductions multiples
                $translations = $sourceSegments[$segment];
                $translatedSegment = $translations[$toLocale] ?? $segment;
                $translatedSegments[] = $translatedSegment;
            } elseif (isset($targetSegments[$segment])) {
                // Le segment est déjà dans la locale cible ou n'a pas besoin de traduction
                $translatedSegments[] = $segment;
            } else {
                // Chercher si c'est une valeur dans les traductions inverses
                $found = false;
                foreach ($sourceSegments as $key => $value) {
                    if (is_array($value) && in_array($segment, $value)) {
                        // Trouvé dans les traductions, récupérer la version cible
                        $translatedSegments[] = $value[$toLocale] ?? $segment;
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    // Pas de traduction trouvée, garder le segment original
                    $translatedSegments[] = $segment;
                }
            }
        }

        return '/' . implode('/', $translatedSegments);
    }

    /**
     * Génère une URL localisée pour une route donnée
     *
     * @param string $routeName Nom de la route
     * @param array $parameters Paramètres de la route
     * @param string|null $locale Locale cible (null = locale courante)
     * @return string URL générée
     */
    public function generateLocalizedUrl(
        string $routeName,
        array $parameters = [],
        ?string $locale = null
    ): string {
        $locale = $locale ?? $this->currentLocale;

        if (!$this->isValidLocale($locale)) {
            $locale = $this->defaultLocale;
        }

        $parameters['_locale'] = $locale;

        try {
            return $this->router->generate($routeName, $parameters);
        } catch (RouteNotFoundException $e) {
            error_log("Route not found: {$routeName}");
            // Fallback vers l'accueil
            return $this->router->generate('home_index', ['_locale' => $locale]);
        }
    }

    /**
     * Récupère toutes les locales supportées
     */
    public function getSupportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    /**
     * Récupère la locale par défaut
     */
    public function getDefaultLocale(): string
    {
        return self::DEFAULT_LOCALE;
    }

    /**
     * Définit la locale courante
     */
    public function setLocale(string $locale): void
    {
        if ($this->isValidLocale($locale)) {
            $this->currentLocale = $locale;
        }
    }

    /**
     * Récupère la locale courante
     */
    public function getLocale(): string
    {
        return $this->currentLocale;
    }

    /**
     * Nettoie le cache des traductions
     * Utile en développement ou après mise à jour des fichiers YAML
     */
    public function clearCache(): void
    {
        $this->routeCache = [];
        $this->segmentCache = [];
    }

    /**
     * Précharge toutes les traductions de routes pour optimisation
     */
    public function warmup(): void
    {
        foreach (self::SUPPORTED_LOCALES as $locale) {
            $this->loadRouteTranslations($locale);
        }
    }
}
