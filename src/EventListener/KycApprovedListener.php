<?php

namespace App\EventListener;

use App\Entity\KycSubmission;
use App\Service\AccountCreationService;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;

class KycApprovedListener
{
    public function __construct(
        private AccountCreationService $accountCreationService,
        private LoggerInterface $logger
    ) {
    }

    public function postUpdate(KycSubmission $kycSubmission, LifecycleEventArgs $event): void
    {
        try {
            $changeSet = $event->getObjectManager()->getUnitOfWork()->getEntityChangeSet($kycSubmission);
            
            // Vérifier si le statut a changé
            if (!isset($changeSet['status'])) {
                return;
            }

            $oldStatus = $changeSet['status'][0] ?? null;
            $newStatus = $changeSet['status'][1] ?? null;

            $this->logger->info('Changement de statut KYC détecté', [
                'kyc_id' => $kycSubmission->getId(),
                'user_id' => $kycSubmission->getUser()->getId(),
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);

            // Si le KYC devient approuvé, créer un compte
            if ($newStatus === KycSubmission::STATUS_APPROVED && $oldStatus !== KycSubmission::STATUS_APPROVED) {
                $this->logger->info('KYC approuvé, création automatique du compte', [
                    'kyc_id' => $kycSubmission->getId(),
                    'user_id' => $kycSubmission->getUser()->getId()
                ]);
                
                $this->accountCreationService->createDefaultAccountForUser($kycSubmission->getUser());
            }

            // Si un KYC approuvé devient rejeté, suspendre les comptes
            if ($newStatus === KycSubmission::STATUS_REJECTED && $oldStatus === KycSubmission::STATUS_APPROVED) {
                $this->logger->info('KYC rejeté après approbation, suspension des comptes', [
                    'kyc_id' => $kycSubmission->getId(),
                    'user_id' => $kycSubmission->getUser()->getId()
                ]);
                
                $this->accountCreationService->suspendUserAccounts($kycSubmission->getUser());
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur dans KycApprovedListener', [
                'kyc_id' => $kycSubmission->getId(),
                'user_id' => $kycSubmission->getUser()->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Ne pas relancer l'exception pour éviter de bloquer EasyAdmin
            // L'erreur est loggée pour investigation
        }
    }
}
