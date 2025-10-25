<?php

namespace App\EventListener;

use App\Service\TransferManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, method: 'onKernelRequest', priority: 5)]
class TransferExpirationListener
{
    private \DateTimeImmutable $lastCheck;
    
    public function __construct(
        private TransferManager $transferManager,
        private LoggerInterface $logger
    ) {
        // Initialiser avec une date ancienne pour forcer le premier check
        $this->lastCheck = new \DateTimeImmutable('2000-01-01');
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Ne traiter que les requêtes principales (pas les sous-requêtes)
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        
        // Ne traiter que les requêtes liées aux virements ou au banking
        $route = $request->attributes->get('_route');
        if (!$this->shouldProcessExpiration($route)) {
            return;
        }

        $now = new \DateTimeImmutable();
        
        // Vérifier les expirations seulement toutes les 5 minutes maximum
        if ($now->diff($this->lastCheck)->i < 5) {
            return;
        }

        try {
            $expiredCount = $this->transferManager->processExpiredCodes();
            
            if ($expiredCount > 0) {
                $this->logger->info('Processed expired transfer codes', [
                    'expired_count' => $expiredCount,
                    'processed_at' => $now->format('Y-m-d H:i:s')
                ]);
            }
            
            $this->lastCheck = $now;
        } catch (\Exception $e) {
            $this->logger->error('Error processing expired transfer codes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function shouldProcessExpiration(?string $route): bool
    {
        if (!$route) {
            return false;
        }

        $transferRoutes = [
            'banking_transfers',
            'banking_transfer_new',
            'banking_transfer_validate',
            'banking_transfer_details',
            'banking_transfer_cancel',
            'admin_transfers',
            'admin_transfer_details',
            'admin_transfer_add_code',
            'admin_transfer_generate_code',
            'admin_transfer_complete',
            'admin_transfer_unblock_user',
            'banking_dashboard' // Pour le dashboard banking
        ];

        return in_array($route, $transferRoutes, true);
    }
}
