<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * LocaleRouteSubscriber 
 * Configure le contexte du routeur pour inclure automatiquement la locale courante
 */
class LocaleRouteSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 16], // Après LocaleSubscriber mais avant les contrôleurs
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $locale = $request->getLocale();
        
        // Configurer le contexte du générateur d'URL pour inclure la locale par défaut
        $context = $this->urlGenerator->getContext();
        
        // Définir la locale dans les paramètres par défaut du contexte
        $defaultParams = $context->getParameters();
        $defaultParams['_locale'] = $locale;
        $context->setParameters($defaultParams);
    }
}
