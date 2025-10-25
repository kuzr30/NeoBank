<?php

namespace App\Listener;

use App\Entity\User;
use App\Service\ProfileNotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;

/**
 * Listener pour détecter les mises à jour de profil utilisateur
 * Respecte le principe Open/Closed et Single Responsibility
 */
#[AsDoctrineListener(event: Events::postUpdate)]
class ProfileUpdateNotificationListener
{
    public function __construct(
        private ProfileNotificationService $profileNotificationService,
        private LoggerInterface $logger
    ) {
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        // Ne traiter que les entités User
        if (!$entity instanceof User) {
            return;
        }

        try {
            // Détecter les champs modifiés
            $changedFields = $this->profileNotificationService->detectChangedFields($entity);

            // Filtrer les champs qui ne nécessitent pas de notification
            $notifiableFields = $this->filterNotifiableChanges($changedFields);

            // Envoyer la notification seulement si des champs pertinents ont changé
            if (!empty($notifiableFields)) {
                $this->profileNotificationService->sendProfileUpdateNotification($entity, $notifiableFields);
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du traitement de la notification de mise à jour de profil', [
                'userId' => $entity->getId(),
                'error' => $e->getMessage()
            ]);
            // Ne pas faire échouer l'opération principale
        }
    }

    /**
     * Filtre les changements qui nécessitent une notification
     */
    private function filterNotifiableChanges(array $changedFields): array
    {
        // Champs qui ne déclenchent pas de notification
        $excludedFields = [
            'lastLoginAt',
            'updatedAt',
            'profilePictureFile', // Le fichier lui-même
            'emailVerificationToken',
            'emailVerificationTokenExpiresAt',
            'activationToken',
            'activationTokenExpiresAt',
            'password', // Les changements de mot de passe ont leur propre système
            'passwordChangedAt'
        ];

        return array_filter($changedFields, function($fieldChange) use ($excludedFields) {
            return !in_array($fieldChange['field'], $excludedFields);
        });
    }
}
