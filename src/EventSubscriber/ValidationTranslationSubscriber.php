<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Force la locale du translator pour s'assurer que les messages de validation
 * sont traduits dans la bonne langue
 */
class ValidationTranslationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TranslatorInterface $translator
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 15], // Priorité après LocaleSubscriber
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $locale = $request->getLocale();
        
        // Force la locale du translator pour s'assurer que les messages de validation
        // sont traduits dans la langue de la requête
        $this->translator->setLocale($locale);
    }
}
