<?php

namespace App\EventSubscriber;

use App\Service\CompanySettingsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * Injecte automatiquement les variables d'entreprise comme variables globales Twig
 * Ces variables seront disponibles dans tous les templates et traductions
 */
class CompanyVariablesInjectorSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Environment $twig,
        private readonly CompanySettingsService $companySettingsService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Injecter les variables d'entreprise comme variables globales Twig
        // Ces variables seront automatiquement utilisées pour remplacer les placeholders %company_name%, etc.
        $this->twig->addGlobal('company_name', $this->companySettingsService->getCompanyName() ?? 'SEDEF BANK');
        $this->twig->addGlobal('company_phone', $this->companySettingsService->getPhone() ?? '+33 1 23 45 67 89');
        $this->twig->addGlobal('company_email', $this->companySettingsService->getEmail() ?? 'contact@sedefbank.com');
        $this->twig->addGlobal('company_address', $this->companySettingsService->getAddress() ?? '3 Rue du Commandant Cousteau, 91300 Massy');
        $this->twig->addGlobal('company_website', $this->companySettingsService->getWebsite() ?? 'www.sedefbank.com');
    }
}
