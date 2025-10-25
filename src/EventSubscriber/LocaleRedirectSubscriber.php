<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LocaleRedirectSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        // Routes à ignorer (admin, API, etc.)
        $ignoredPrefixes = ['/admin', '/api', '/_', '/assets', '/build'];
        foreach ($ignoredPrefixes as $prefix) {
            if (str_starts_with($pathInfo, $prefix)) {
                return;
            }
        }

        // Si la route commence déjà par une locale, on ne fait rien
        if (preg_match('#^/(fr|nl|de|en|es)(/|$)#', $pathInfo)) {
            return;
        }

        // Routes spécifiques à rediriger avec locale
        $routesToRedirect = [
            '/login',
            '/register',
            '/profile',
            '/banking',
            '/credit',
            '/simulation'
        ];

        foreach ($routesToRedirect as $route) {
            if ($pathInfo === $route || str_starts_with($pathInfo, $route . '/')) {
                $preferredLocale = $this->getPreferredLocale($request);
                $newPath = '/' . $preferredLocale . $pathInfo;
                
                // Préserver les paramètres de requête
                $queryString = $request->getQueryString();
                if ($queryString) {
                    $newPath .= '?' . $queryString;
                }
                
                $event->setResponse(new RedirectResponse($newPath, 301));
                return;
            }
        }
    }

    private function getPreferredLocale($request): string
    {
        // 1. Vérifier la session
        if ($request->hasSession() && $request->getSession()->has('_locale')) {
            $sessionLocale = $request->getSession()->get('_locale');
            if (in_array($sessionLocale, ['fr', 'nl', 'de', 'en', 'es'])) {
                return $sessionLocale;
            }
        }

        // 2. Vérifier l'en-tête Accept-Language
        $preferredLanguage = $request->getPreferredLanguage(['fr', 'nl', 'de', 'en', 'es']);
        if ($preferredLanguage) {
            return $preferredLanguage;
        }

        // 3. Locale par défaut
        return 'fr';
    }
}
