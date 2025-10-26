<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * LocaleSubscriber pour la gestion automatique des langues avec URL préfixées
 * Implémente les principes SOLID et suit les bonnes pratiques Symfony 7
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    private const SUPPORTED_LOCALES = ['fr', 'nl', 'de', 'en', 'es'];
    private const DEFAULT_LOCALE = 'fr';

    public function __construct(
        private RequestStack $requestStack
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // Priorité 25 pour s'exécuter AVANT LocaleRedirectSubscriber (qui était à 20)
            // Ordre garanti: LocaleSubscriber(25) → LocaleRouteSubscriber(16) → TranslationSubscriber(15)
            KernelEvents::REQUEST => [['onKernelRequest', 25]],
            // Gestion des exceptions pour préserver la locale
            KernelEvents::EXCEPTION => [['onKernelException', 10]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Ne traiter que les requêtes principales
        if (!$event->isMainRequest()) {
            return;
        }

        // Récupérer la locale depuis l'URL ou détecter la préférée
        $locale = $this->detectLocaleFromRequest($request);
        
        // Définir la locale dans la requête
        $request->setLocale($locale);
        
        // Sauvegarder en session si une session existe
        $session = $request->getSession();
        if ($session) {
            $session->set('_locale', $locale);
        }
    }

    private function detectLocaleFromRequest($request): string
    {
        // 1. Priorité: locale dans l'URL (paramètre de route)
        $routeLocale = $request->attributes->get('_locale');
        if ($routeLocale && $this->isLocaleSupported($routeLocale)) {
            return $routeLocale;
        }

        // 2. Priorité: locale depuis le chemin URL (pour les routes sans paramètre)
        $pathInfo = $request->getPathInfo();
        if (preg_match('#^/([a-z]{2})(/|$)#', $pathInfo, $matches)) {
            $pathLocale = $matches[1];
            if ($this->isLocaleSupported($pathLocale)) {
                return $pathLocale;
            }
        }

        // 3. Priorité: locale sauvegardée en session
        $session = $request->getSession();
        if ($session) {
            $sessionLocale = $session->get('_locale');
            if ($sessionLocale && $this->isLocaleSupported($sessionLocale)) {
                return $sessionLocale;
            }
        }

        // 4. Priorité: locale depuis le header Accept-Language
        $preferredLanguage = $request->getPreferredLanguage(self::SUPPORTED_LOCALES);
        if ($preferredLanguage && $this->isLocaleSupported($preferredLanguage)) {
            return $preferredLanguage;
        }

        // 5. Fallback: locale par défaut
        return self::DEFAULT_LOCALE;
    }

    private function isLocaleSupported(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED_LOCALES, true);
    }

    public static function getSupportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    public static function getDefaultLocale(): string
    {
        return self::DEFAULT_LOCALE;
    }

    /**
     * Gère les exceptions pour préserver la locale dans les pages d'erreur
     * Fusionné depuis ExceptionLocaleSubscriber
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        // Extraire la locale de l'URL pour les pages d'erreur
        $pathInfo = $request->getPathInfo();

        if (preg_match('#^/([a-z]{2})(/.*)?$#', $pathInfo, $matches)) {
            $locale = $matches[1];

            // Vérifier que c'est une locale supportée
            if ($this->isLocaleSupported($locale)) {
                // Forcer la locale pour la requête et la session
                $request->setLocale($locale);

                if ($request->hasSession()) {
                    $request->getSession()->set('_locale', $locale);
                }
            }
        }
    }
}
