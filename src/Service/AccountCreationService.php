<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Account;
use App\Enum\AccountTypeEnum;
use App\Enum\AccountStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AccountCreationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private NotificationService $notificationService,
        private IbanGeneratorService $ibanGeneratorService
    ) {
    }

    /**
     * Crée automatiquement un compte courant pour un utilisateur après validation KYC
     */
    public function createDefaultAccountForUser(User $user): Account
    {
        try {
            $this->logger->info('Début création automatique de compte après validation KYC', [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail()
            ]);

            // Vérifier si l'utilisateur a déjà un compte courant
            $existingAccount = $this->entityManager->getRepository(Account::class)
                ->findOneBy(['owner' => $user, 'type' => AccountTypeEnum::CHECKING->value]);

            if ($existingAccount) {
                $this->logger->warning('Utilisateur a déjà un compte courant', [
                    'user_id' => $user->getId(),
                    'account_id' => $existingAccount->getId()
                ]);
                return $existingAccount;
            }

            // Créer le nouveau compte
            $account = new Account();
            $account->setOwner($user);
            $account->setType(AccountTypeEnum::CHECKING->value);
            $account->setStatus(AccountStatusEnum::ACTIVE->value);
            $account->setBalance('0.00');
            $account->setBalance('0.00');
            $account->setCurrency('EUR');
            
            // Générer l'IBAN selon le pays de l'utilisateur
            $iban = $this->ibanGeneratorService->generateIbanForUser($user);
            $account->setGeneratedIban($iban);
            
            // Générer le numéro de compte basé sur l'IBAN généré
            $account->generateAccountNumberFromIban();
            
            $this->logger->info('IBAN et compte générés', [
                'user_id' => $user->getId(),
                'user_country' => $user->getCountry(),
                'generated_iban' => $iban,
                'account_number' => $account->getAccountNumber()
            ]);
            // Le numéro de compte est maintenant généré selon le pays

            $this->entityManager->persist($account);
            $this->entityManager->flush();

            $this->logger->info('Compte créé avec succès', [
                'user_id' => $user->getId(),
                'account_id' => $account->getId(),
                'account_number' => $account->getAccountNumber(),
                'iban' => $account->getIban()
            ]);

            // Envoyer notification à l'utilisateur (sans faire échouer si erreur email)
            try {
                $this->notificationService->sendAccountCreatedNotification($user, $account);
            } catch (\Exception $emailException) {
                $this->logger->warning('Erreur envoi email de notification, mais compte créé', [
                    'user_id' => $user->getId(),
                    'account_id' => $account->getId(),
                    'email_error' => $emailException->getMessage()
                ]);
            }

            return $account;
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création automatique du compte', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Relancer l'exception pour que l'EventListener puisse la gérer
            throw $e;
        }
    }

    /**
     * Suspend tous les comptes d'un utilisateur (si KYC rejeté après approbation)
     */
    public function suspendUserAccounts(User $user): void
    {
        $accounts = $this->entityManager->getRepository(Account::class)
            ->findBy(['owner' => $user, 'status' => AccountStatusEnum::ACTIVE->value]);

        foreach ($accounts as $account) {
            $account->setStatus(AccountStatusEnum::SUSPENDED->value);
            $this->logger->info('Compte suspendu suite au rejet KYC', [
                'user_id' => $user->getId(),
                'account_id' => $account->getId()
            ]);
        }

        if (!empty($accounts)) {
            $this->entityManager->flush();
            $this->notificationService->sendAccountSuspendedNotification($user);
        }
    }
}
