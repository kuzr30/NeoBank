<?php

namespace App\Service;

use App\Entity\Transfer;
use App\Entity\TransferCode;
use App\Entity\TransferAttempt;
use App\Entity\User;
use App\Entity\BankAccount;
use App\Repository\TransferRepository;
use App\Repository\TransferCodeRepository;
use App\Repository\TransferAttemptRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;

class TransferManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TransferRepository $transferRepository,
        private TransferCodeRepository $transferCodeRepository,
        private TransferAttemptRepository $transferAttemptRepository,
        private RequestStack $requestStack,
        private LoggerInterface $logger,
        private TransferNotificationService $notificationService
    ) {
    }

    /**
     * Initie un nouveau virement
     */
    public function initiateTransfer(User $user, BankAccount $destinationAccount, string $amount, ?string $description = null): Transfer
    {
        // Vérifier si l'utilisateur est bloqué
        if ($this->isUserBlocked($user)) {
            throw new \Exception('Votre compte est bloqué. Contactez l\'administration.');
        }

        // Vérifier si l'utilisateur a suffisamment de fonds
        $userAccount = $user->getAccounts()->first();
        if (!$userAccount || !$userAccount->canDebit($amount)) {
            throw new \Exception('Solde insuffisant pour effectuer ce virement.');
        }

        // DÉBITER IMMÉDIATEMENT le montant du compte (nouvelle logique)
        $userAccount->debit($amount);
        $this->entityManager->persist($userAccount);

        // Créer le virement
        $transfer = new Transfer();
        $transfer->setUser($user)
                 ->setDestinationAccount($destinationAccount)
                 ->setAmount($amount)
                 ->setDescription($description)
                 ->setStatus('pending');

        $this->entityManager->persist($transfer);
        $this->entityManager->flush();

        $this->logger->info('Nouveau virement initié avec débit immédiat', [
            'transfer_id' => $transfer->getId(),
            'user_id' => $user->getId(),
            'amount' => $amount,
            'destination' => $destinationAccount->getIban(),
            'account_balance_after_debit' => $userAccount->getBalance()
        ]);

        // Envoyer notification email
        $this->notificationService->notifyTransferCreated($transfer);

        return $transfer;
    }

    /**
     * Ajoute un code pour un virement (par l'admin)
     */
    public function addCodeToTransfer(Transfer $transfer, string $codeName, string $codeValue): TransferCode
    {
        $nextOrder = $this->transferCodeRepository->getNextCodeOrder($transfer);

        $transferCode = new TransferCode();
        $transferCode->setTransfer($transfer)
                     ->setCodeOrder($nextOrder)
                     ->setCodeName($codeName)
                     ->setCodeValue($codeValue);

        // Si le virement était en cours d'exécution, le remettre en attente
        if ($transfer->getStatus() === 'executing') {
            $transfer->setStatus('pending');
        }

        $this->entityManager->persist($transferCode);
        $this->entityManager->flush();

        $this->logger->info('Code ajouté au virement', [
            'transfer_id' => $transfer->getId(),
            'code_order' => $nextOrder,
            'code_name' => $codeName,
            'status_changed_to' => $transfer->getStatus()
        ]);

        // Envoyer notification d'ajout de code
        $this->notificationService->notifyCodeAdded($transfer, $transferCode);

        return $transferCode;
    }

    /**
     * Valide un code saisi par l'utilisateur
     */
    public function validateTransferCode(Transfer $transfer, string $inputCode): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'transfer_status' => $transfer->getStatus(),
            'next_code_needed' => false,
            'transfer_completed' => false,
            'account_blocked' => false
        ];

        // Vérifier si le compte est bloqué
        if ($transfer->isAccountBlocked()) {
            $result['message'] = 'Votre compte est bloqué. Contactez l\'administration.';
            $result['account_blocked'] = true;
            return $result;
        }

        // Vérifier si le virement a expiré
        if ($transfer->isExpired()) {
            $transfer->setStatus('expired');
            $this->entityManager->flush();
            $result['message'] = 'Ce virement a expiré.';
            return $result;
        }

        // Obtenir le code actuel
        $currentCode = $transfer->getCurrentCode();
        if (!$currentCode) {
            $result['message'] = 'Aucun code en attente pour ce virement.';
            return $result;
        }

        // Vérifier si le code peut encore être validé
        if (!$currentCode->canBeValidated()) {
            $result['message'] = 'Ce code ne peut plus être validé (trop de tentatives échouées).';
            return $result;
        }

        // Enregistrer la tentative
        $attempt = $this->recordAttempt($transfer, $currentCode, $inputCode);

        // Vérifier le code
        if (strtoupper($inputCode) === $currentCode->getCodeValue()) {
            // Code correct
            $currentCode->validate();
            $transfer->setCurrentCodeIndex($transfer->getCurrentCodeIndex() + 1);
            $transfer->setStatus('executing');
            
            $attempt->setIsSuccess(true);
            
            $this->entityManager->flush();

            $result['success'] = true;
            $result['message'] = 'Code validé avec succès. Virement en cours d\'exécution.';
            $result['transfer_status'] = 'executing';

            // Programmer l'expiration du code (6H)
            $this->scheduleCodeExpiration($currentCode);

            $this->logger->info('Code validé avec succès', [
                'transfer_id' => $transfer->getId(),
                'code_order' => $currentCode->getCodeOrder()
            ]);

            // Envoyer notification email
            $this->notificationService->notifyCodeValidated($transfer, $currentCode);

            // Vérifier si tous les codes sont validés
            if ($transfer->isFullyValidated()) {
                $this->notificationService->notifyAllCodesValidated($transfer);
            }

        } else {
            // Code incorrect
            $currentCode->incrementFailedAttempts();
            $transfer->incrementFailedAttempts();

            // NOUVEAU : Blocage après 3 tentatives (au lieu de 9)
            if ($currentCode->getFailedAttempts() >= 3) {
                $transfer->setIsAccountBlocked(true);
                $transfer->setStatus('blocked');
                $result['message'] = 'Compte bloqué après 3 tentatives échouées. Contactez l\'administration.';
                $result['account_blocked'] = true;
                
                // Notification de blocage
                $this->notificationService->notifyUserBlocked($transfer);
                $this->notificationService->notifyAdminSuspiciousActivity(
                    $transfer, 
                    'Compte bloqué après 3 tentatives de validation incorrectes'
                );
            } else {
                $result['message'] = sprintf(
                    'Code incorrect. %d tentative(s) restante(s).',
                    3 - $currentCode->getFailedAttempts()
                );
            }

            $this->entityManager->flush();

            $this->logger->warning('Tentative de code échouée', [
                'transfer_id' => $transfer->getId(),
                'code_order' => $currentCode->getCodeOrder(),
                'failed_attempts' => $currentCode->getFailedAttempts(),
                'total_failed_attempts' => $transfer->getFailedAttemptsTotal()
            ]);
        }

        return $result;
    }

    /**
     * Exécute physiquement le virement (marque comme terminé)
     * Note: Le débit a déjà été effectué lors de l'initiation
     */
    public function executeTransfer(Transfer $transfer): void
    {
        try {
            // L'argent a déjà été débité lors de l'initiation
            // On marque simplement le virement comme terminé
            $transfer->setStatus('completed');
            $transfer->setExecutedAt(new \DateTimeImmutable());

            $this->entityManager->flush();

            $this->logger->info('Virement exécuté avec succès', [
                'transfer_id' => $transfer->getId(),
                'amount' => $transfer->getAmount(),
                'note' => 'Argent déjà débité lors de l\'initiation'
            ]);

            // Envoyer notification d'exécution
            $this->notificationService->notifyTransferExecuted($transfer);

        } catch (\Exception $e) {
            $transfer->setStatus('failed');
            $this->entityManager->flush();
            
            $this->logger->error('Échec de l\'exécution du virement', [
                'transfer_id' => $transfer->getId(),
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Vérifie si un utilisateur est bloqué
     */
    public function isUserBlocked(User $user): bool
    {
        return $this->transferRepository->isUserBlocked($user);
    }

    /**
     * Débloque un utilisateur (fonction admin)
     */
    public function unblockUser(User $user): void
    {
        $blockedTransfers = $this->entityManager->getRepository(Transfer::class)
            ->findBy(['user' => $user, 'isAccountBlocked' => true]);

        foreach ($blockedTransfers as $transfer) {
            $transfer->setIsAccountBlocked(false);
        }

        $this->entityManager->flush();

        $this->logger->info('Utilisateur débloqué', ['user_id' => $user->getId()]);
    }

    /**
     * Traite les codes expirés (commande cron)
     */
    public function processExpiredCodes(): int
    {
        $expiredCodes = $this->transferCodeRepository->findExpiredCodes();
        $count = 0;

        foreach ($expiredCodes as $code) {
            $code->setStatus('expired');
            $transfer = $code->getTransfer();
            
            // Demander le prochain code
            $transfer->setCurrentCodeIndex($transfer->getCurrentCodeIndex() + 1);
            
            $this->logger->info('Code expiré - demande du code suivant', [
                'transfer_id' => $transfer->getId(),
                'expired_code_order' => $code->getCodeOrder(),
                'next_code_order' => $transfer->getCurrentCodeIndex()
            ]);

            // Envoyer notification d'expiration
            $this->notificationService->notifyCodeExpired($transfer, $code);
            
            $count++;
        }

        $this->entityManager->flush();
        return $count;
    }

    /**
     * Enregistre une tentative de validation
     */
    private function recordAttempt(Transfer $transfer, TransferCode $code, string $inputCode): TransferAttempt
    {
        $request = $this->requestStack->getCurrentRequest();
        
        $attempt = new TransferAttempt();
        $attempt->setTransfer($transfer)
                ->setTransferCode($code)
                ->setAttemptedCode($inputCode)
                ->setIsSuccess(false);

        if ($request) {
            $attempt->setIpAddress($request->getClientIp())
                    ->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($attempt);
        
        return $attempt;
    }

    /**
     * Annule un virement
     */
    public function cancelTransfer(Transfer $transfer): void
    {
        if (!in_array($transfer->getStatus(), ['pending', 'executing'])) {
            throw new \Exception('Ce virement ne peut pas être annulé.');
        }

        $userAccount = $transfer->getUser()->getAccounts()->first();
        $originalStatus = $transfer->getStatus();
        
        // Log de début pour débugger
        $this->logger->info('Début annulation virement', [
            'transfer_id' => $transfer->getId(),
            'user_id' => $transfer->getUser()->getId(),
            'amount' => $transfer->getAmount(),
            'status' => $originalStatus,
            'account_id' => $userAccount ? $userAccount->getId() : 'null',
            'balance_before_any_action' => $userAccount ? $userAccount->getBalance() : 'null'
        ]);

        // TOUJOURS recréditer le montant car l'argent est TOUJOURS débité lors de l'initiation
        if ($userAccount) {
            $balanceBefore = $userAccount->getBalance();
            
            $userAccount->credit($transfer->getAmount());
            
            // S'assurer que l'account modifié est persisté
            $this->entityManager->persist($userAccount);
            
            $this->logger->info('Montant recredité suite à annulation', [
                'transfer_id' => $transfer->getId(),
                'user_id' => $transfer->getUser()->getId(),
                'amount' => $transfer->getAmount(),
                'balance_before' => $balanceBefore,
                'balance_after' => $userAccount->getBalance(),
                'original_status' => $originalStatus
            ]);
        }

        $transfer->setStatus('cancelled');
        $this->entityManager->flush();

        $this->logger->info('Virement annulé avec succès', [
            'transfer_id' => $transfer->getId(),
            'user_id' => $transfer->getUser()->getId(),
            'original_status' => $originalStatus,
            'final_balance' => $userAccount ? $userAccount->getBalance() : 'null'
        ]);

        // Envoyer notification d'annulation
        $this->notificationService->notifyTransferCancelled($transfer);
    }

    /**
     * Force la validation d'un virement (bypass les codes)
     */
    public function forceValidateTransfer(Transfer $transfer): void
    {
        if (!in_array($transfer->getStatus(), ['pending', 'executing'])) {
            throw new \Exception('Ce virement ne peut pas être validé.');
        }

        // Marquer tous les codes comme validés
        foreach ($transfer->getTransferCodes() as $code) {
            if ($code->getStatus() === 'pending') {
                $code->setStatus('validated');
                $code->setValidatedAt(new \DateTimeImmutable());
            }
        }

        // Marquer le virement comme complètement validé et exécuter
        $transfer->setStatus('executing');
        $this->entityManager->flush();

        // Exécuter le virement
        $this->executeTransfer($transfer);
    }

    /**
     * Bloque un virement
     */
    public function blockTransfer(Transfer $transfer): void
    {
        if (!in_array($transfer->getStatus(), ['pending', 'executing'])) {
            throw new \Exception('Ce virement ne peut pas être bloqué.');
        }

        $transfer->setStatus('blocked');
        $transfer->setIsAccountBlocked(true);
        $this->entityManager->flush();

        $this->logger->info('Virement bloqué par le service transfert', [
            'transfer_id' => $transfer->getId(),
            'user_id' => $transfer->getUser()->getId(),
        ]);
    }

    /**
     * Débloque un virement
     */
    public function unblockTransfer(Transfer $transfer): void
    {
        if ($transfer->getStatus() !== 'blocked') {
            throw new \Exception('Ce virement n\'est pas bloqué.');
        }

        $transfer->setStatus('pending');
        $transfer->setIsAccountBlocked(false);
        $this->entityManager->flush();

        $this->logger->info('Virement débloqué par admin', [
            'transfer_id' => $transfer->getId(),
            'user_id' => $transfer->getUser()->getId(),
        ]);
    }

    /**
     * Programme l'expiration d'un code (6H après validation)
     */
    private function scheduleCodeExpiration(TransferCode $code): void
    {
        // Dans un vrai système, ceci déclencherait une tâche cron
        // Pour le moment, on se contente de définir l'expiration
        $code->setExpiresAt(new \DateTimeImmutable('+6 hours'));
        $this->entityManager->flush();
    }

    /**
     * Génère automatiquement un code pour un virement
     */
    public function generateCode(Transfer $transfer): TransferCode
    {
        $codeValue = TransferCode::generateRandomCode();
        return $this->addCodeToTransfer($transfer, 'Code Auto', $codeValue);
    }

    /**
     * Génère manuellement un code pour un virement
     */
    public function generateManualCode(Transfer $transfer): TransferCode
    {
        $codeValue = TransferCode::generateRandomCode();
        return $this->addCodeToTransfer($transfer, 'Code Manuel', $codeValue);
    }

    /**
     * Simule la validation d'un code par l'utilisateur (pour l'administration)
     * Fait avancer le processus de validation étape par étape
     */
    public function simulateUserValidation(Transfer $transfer): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'transfer_status' => $transfer->getStatus(),
            'next_code_needed' => false,
            'transfer_completed' => false
        ];

        // Vérifier qu'il y a un code en attente
        $currentCode = $transfer->getCurrentCode();
        if (!$currentCode) {
            $result['message'] = 'Aucun code en attente. Générez d\'abord un code.';
            return $result;
        }

        // Valider le code actuel
        $currentCode->validate();
        $transfer->setCurrentCodeIndex($transfer->getCurrentCodeIndex() + 1);
        $transfer->setStatus('executing');
        
        $this->entityManager->flush();

        $result['success'] = true;
        $result['transfer_status'] = 'executing';

        // Vérifier s'il faut continuer le processus
        if ($transfer->isFullyValidated()) {
            // Tous les codes sont validés, mais on ne finalise pas automatiquement
            $result['message'] = 'Code validé. Tous les codes sont maintenant validés. Utilisez "Valider final" pour terminer le virement.';
            $result['next_code_needed'] = false;
            $result['transfer_completed'] = false;
        } else {
            // Il faut générer le prochain code
            $result['message'] = 'Code validé. Générez le prochain code pour continuer.';
            $result['next_code_needed'] = true;
        }

        $this->logger->info('Validation simulée par l\'administration', [
            'transfer_id' => $transfer->getId(),
            'code_order' => $currentCode->getCodeOrder(),
            'all_codes_validated' => $transfer->isFullyValidated()
        ]);

        return $result;
    }
}
