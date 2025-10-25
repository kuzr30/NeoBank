<?php

namespace App\EventSubscriber;

use App\Service\ProfessionalTranslationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * TranslationSubscriber pour initialiser automatiquement les traductions
 * Principe de responsabilité unique (SRP) - séparation des concerns
 */
class TranslationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ProfessionalTranslationService $translationService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // Priorité plus basse que LocaleSubscriber pour s'exécuter après
            KernelEvents::REQUEST => [['onKernelRequest', 15]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Ne traiter que les requêtes principales
        if (!$event->isMainRequest()) {
            return;
        }

        // Récupérer la locale définie par LocaleSubscriber
        $locale = $request->getLocale();
        
        // Initialiser le service de traduction avec la locale
        $this->translationService->setLocale($locale);
        
        // Précharger les traductions globales
        $this->preloadGlobalTranslations();
    }

    private function preloadGlobalTranslations(): void
    {
        // Précharger les traductions communes pour optimiser les performances
        $this->translationService->getSection('nav');
        $this->translationService->getSection('buttons');
        $this->translationService->getSection('footer');
    }
}
