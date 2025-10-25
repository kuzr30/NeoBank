<?php

namespace App\Service;

use App\Entity\User;
use App\Message\EmailVerificationMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour gérer la validation des changements d'email
 * Respecte le principe Single Responsibility
 */
class EmailVerificationService
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Initie le processus de changement d'email
     */
    public function initiateEmailChange(User $user, string $newEmail): void
    {
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('L\'adresse email n\'est pas valide');
        }

        if ($newEmail === $user->getEmail()) {
            throw new \InvalidArgumentException('La nouvelle adresse email est identique à l\'actuelle');
        }

        // Vérifier que l'email n'est pas déjà utilisé
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $newEmail]);
        
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            throw new \InvalidArgumentException('Cette adresse email est déjà utilisée');
        }

        // Générer le token et stocker l'email en attente
        $token = $user->generateEmailVerificationToken();
        $user->setPendingEmail($newEmail);

        $this->entityManager->flush();

        $this->sendEmailVerification($user, $newEmail, $token);

        $this->logger->info('Processus de changement d\'email initié', [
            'userId' => $user->getId(),
            'currentEmail' => $user->getEmail(),
            'pendingEmail' => $newEmail
        ]);
    }

    /**
     * Valide et confirme le changement d'email
     */
    public function confirmEmailChange(string $token): User
    {
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['emailVerificationToken' => $token]);

        if (!$user) {
            throw new \InvalidArgumentException('Token de validation invalide');
        }

        if (!$user->isEmailVerificationTokenValid()) {
            throw new \InvalidArgumentException('Le token de validation a expiré');
        }

        if (!$user->getPendingEmail()) {
            throw new \InvalidArgumentException('Aucune adresse email en attente de validation');
        }

        // Effectuer le changement
        $oldEmail = $user->getEmail();
        $newEmail = $user->getPendingEmail();

        $user->setEmail($newEmail);
        $user->setPendingEmail(null);
        $user->clearEmailVerificationToken();
        $user->setEmailVerified(true);
        $user->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->logger->info('Changement d\'email confirmé', [
            'userId' => $user->getId(),
            'oldEmail' => $oldEmail,
            'newEmail' => $newEmail
        ]);

        return $user;
    }

    /**
     * Envoie l'email de validation
     */
    private function sendEmailVerification(User $user, string $newEmail, string $token): void
    {
        $verificationUrl = $this->urlGenerator->generate(
            'app_email_verification',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $subject = $this->translator->trans(
            'email_verification.subject',
            [],
            'messages',
            $user->getLanguage() ?? 'fr'
        );

        $context = [
            'user' => $user,
            'firstName' => $user->getFirstName(),
            'newEmail' => $newEmail,
            'verificationUrl' => $verificationUrl,
            'token' => $token,
            'expirationDate' => $user->getEmailVerificationTokenExpiresAt(),
            'locale' => $user->getLanguage() ?? 'fr'
        ];

        $message = new EmailVerificationMessage(
            $newEmail, // Envoyer à la nouvelle adresse
            $subject,
            $context,
            $user->getFirstName() . ' ' . $user->getLastName()
        );

        $this->messageBus->dispatch($message);

        $this->logger->info('Email de validation programmé', [
            'userId' => $user->getId(),
            'newEmail' => $newEmail
        ]);
    }

    /**
     * Annule un changement d'email en cours
     */
    public function cancelEmailChange(User $user): void
    {
        if (!$user->getPendingEmail()) {
            return;
        }

        $user->setPendingEmail(null);
        $user->clearEmailVerificationToken();

        $this->entityManager->flush();

        $this->logger->info('Changement d\'email annulé', [
            'userId' => $user->getId()
        ]);
    }
}
