<?php

namespace App\Service;

use App\Entity\User;
use App\Message\ProfileUpdateNotificationEmailMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour gérer les notifications de mise à jour de profil
 * Respecte le principe Single Responsibility
 */
class ProfileNotificationService
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Envoie une notification de mise à jour de profil
     */
    public function sendProfileUpdateNotification(User $user, array $changedFields = []): void
    {
        try {
            $subject = $this->translator->trans('profile.notification.update.subject', [], 'messages', $user->getLanguage() ?? 'fr');
            
            $context = [
                'user' => $user,
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'changedFields' => $changedFields,
                'updateDate' => new \DateTimeImmutable(),
                'locale' => $user->getLanguage() ?? 'fr'
            ];

            $message = new ProfileUpdateNotificationEmailMessage(
                $user->getEmail(),
                $context,
                $user->getFirstName() . ' ' . $user->getLastName()
            );

            $this->messageBus->dispatch($message);

            $this->logger->info('Notification de mise à jour de profil programmée', [
                'userId' => $user->getId(),
                'email' => $user->getEmail(),
                'changedFields' => $changedFields
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la programmation de la notification de profil', [
                'userId' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage()
            ]);
            // Ne pas faire échouer la mise à jour du profil pour un problème d'email
        }
    }

    /**
     * Détermine quels champs ont changé lors d'une mise à jour
     */
    public function detectChangedFields(User $user): array
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();
        $changeSet = $unitOfWork->getEntityChangeSet($user);
        
        $changedFields = [];
        $fieldLabels = [
            'firstName' => 'Prénom',
            'lastName' => 'Nom',
            'phone' => 'Téléphone',
            'address' => 'Adresse',
            'city' => 'Ville',
            'postalCode' => 'Code postal',
            'country' => 'Pays',
            'birthDate' => 'Date de naissance',
            'language' => 'Langue',
            'currency' => 'Devise',
            'timezone' => 'Fuseau horaire',
            'profilePicture' => 'Photo de profil'
        ];

        foreach ($changeSet as $field => $values) {
            if (isset($fieldLabels[$field])) {
                $changedFields[] = [
                    'field' => $field,
                    'label' => $fieldLabels[$field],
                    'oldValue' => $values[0],
                    'newValue' => $values[1]
                ];
            }
        }

        return $changedFields;
    }
}
