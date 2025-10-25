<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionLocaleSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        
        // Extraire la locale de l'URL pour les pages d'erreur
        $pathInfo = $request->getPathInfo();
        
        if (preg_match('#^/([a-z]{2})(/.*)?$#', $pathInfo, $matches)) {
            $locale = $matches[1];
            
            // Vérifier que c'est une locale supportée
            if (in_array($locale, ['fr', 'nl', 'de', 'en', 'es'])) {
                // Forcer la locale pour la requête et la session
                $request->setLocale($locale);
                
                if ($request->hasSession()) {
                    $request->getSession()->set('_locale', $locale);
                }
            }
        }
    }
}
