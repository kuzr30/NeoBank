<?php

namespace App\Service;

use App\Entity\CreditApplication;
use App\Entity\User;
use App\Entity\Account;
use App\Entity\Transaction;
use App\Entity\SubAccountCredit;
use App\Enum\CreditApplicationStatusEnum;
use App\Message\CreditDecisionEmailMessage;
use App\Message\ContractEmailMessage;
use App\Message\ContractSignedNotificationMessage;
use App\Message\ContractSignedCustomerNotificationMessage;
use App\Message\ContractValidatedNotificationMessage;
use App\Message\FundsDisbursedNotificationMessage;
use App\Repository\UserRepository;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class CreditWorkflowService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private ContractGeneratorService $contractGenerator,
        private CreditApplicationService $creditApplicationService,
        private UserRepository $userRepository,
        private AccountRepository $accountRepository,
        private MailerInterface $mailer,
        private ProfessionalTranslationService $translationService,
        private string $fromEmail,
        private string $adminEmail,
        private string $projectDir
    ) {}

    public function approveApplication(CreditApplication $application, string $adminNotes = null): void
    {
        $this->creditApplicationService->updateStatus(
            $application,
            CreditApplicationStatusEnum::APPROVED,
            $adminNotes ?? $this->translationService->tp('admin_notes.approved', [
                'date' => date('d/m/Y H:i')
            ], 'credit_workflow_service')
        );

        // Envoyer seulement l'email de félicitations
        // Le contrat sera généré APRÈS ajout des frais
        $this->sendDecisionEmail($application, true);
    }

    public function approveAndSendContract(CreditApplication $application, string $adminNotes = null): void
    {
        $this->creditApplicationService->updateStatus(
            $application,
            CreditApplicationStatusEnum::APPROVED,
            $adminNotes ?? $this->translationService->tp('admin_notes.approved', [
                'date' => date('d/m/Y H:i')
            ], 'credit_workflow_service')
        );

        $this->sendDecisionEmail($application, true);
        $this->generateAndSendContract($application);
    }

    public function rejectApplication(CreditApplication $application, string $adminNotes = null): void
    {
        $this->creditApplicationService->updateStatus(
            $application,
            CreditApplicationStatusEnum::REJECTED,
            $adminNotes ?? $this->translationService->tp('admin_notes.rejected', [
                'date' => date('d/m/Y H:i')
            ], 'credit_workflow_service')
        );

        $this->sendDecisionEmail($application, false);
    }

    public function generateAndSendContract(CreditApplication $application): void
    {
        try {
            // Récupérer la langue préférée de l'utilisateur
            $preferredLocale = $this->resolveApplicationLocale($application);
            
            // Générer le contrat dans la langue de l'utilisateur
            $contractPdf = $this->contractGenerator->generateCreditContract($application, $preferredLocale);
            $contractFilename = $this->contractGenerator->getContractFilename($application);

            $this->saveContractFile($application, $contractPdf, $contractFilename);
            $this->sendContractEmail($application, $contractPdf, $contractFilename);

            $this->creditApplicationService->updateStatus(
                $application,
                CreditApplicationStatusEnum::CONTRACT_SENT,
                $this->translationService->tp('admin_notes.contract_generated', [
                    'date' => date('d/m/Y H:i')
                ], 'credit_workflow_service')
            );

        } catch (\Exception $e) {
            error_log($this->translationService->tp('log_messages.contract_generation_error', [
                'id' => $application->getId(),
                'error' => $e->getMessage()
            ], 'credit_workflow_service'));
            throw $e;
        }
    }

    public function notifyContractSigned(CreditApplication $application, ?string $signedContractPath = null): void
    {
        $this->creditApplicationService->updateStatus(
            $application,
            CreditApplicationStatusEnum::CONTRACT_SIGNED,
            $this->translationService->tp('admin_notes.contract_signed', [
                'date' => date('d/m/Y H:i')
            ], 'credit_workflow_service')
        );

        $this->notifyAdminContractSigned($application, $signedContractPath);
    }

    public function markContractAsSigned(CreditApplication $application): void
    {
        $this->creditApplicationService->updateStatus(
            $application,
            CreditApplicationStatusEnum::CONTRACT_SIGNED,
            'Contrat marqué comme signé par l\'administrateur le ' . date('d/m/Y H:i')
        );

        // Envoyer une notification au client pour confirmer la signature
        $this->sendContractSignedCustomerNotification($application);
    }

    public function validateContract(CreditApplication $application): void
    {
        $this->creditApplicationService->updateStatus(
            $application,
            CreditApplicationStatusEnum::CONTRACT_VALIDATED,
            'Contrat validé par l\'administrateur le ' . date('d/m/Y H:i')
        );

        // Envoyer l'email de notification au client
        $this->sendContractValidatedNotification($application);
    }

    public function disburseFunds(CreditApplication $application): void
    {
        // Vérifier que le contrat est validé
        if ($application->getStatus() !== CreditApplicationStatusEnum::CONTRACT_VALIDATED) {
            throw new \Exception($this->translationService->tp('exceptions.contract_not_validated', [], 'credit_workflow_service'));
        }

        $this->disburseFundsInternal($application);

        $this->creditApplicationService->updateStatus(
            $application,
            CreditApplicationStatusEnum::FUNDS_DISBURSED,
            'Fonds débloqués le ' . date('d/m/Y H:i')
        );

        // Envoyer l'email de notification au client
        $this->sendFundsDisbursedNotification($application);
    }

    public function validateContractAndDisburseFunds(CreditApplication $application): void
    {
        $this->creditApplicationService->updateStatus(
            $application,
            CreditApplicationStatusEnum::CONTRACT_VALIDATED,
            'Contrat validé par l\'administrateur le ' . date('d/m/Y H:i')
        );

        // Envoyer l'email de validation de contrat
        $this->sendContractValidatedNotification($application);

        $this->disburseFundsInternal($application);

        $this->creditApplicationService->updateStatus(
            $application,
            CreditApplicationStatusEnum::FUNDS_DISBURSED,
            'Fonds débloqués le ' . date('d/m/Y H:i')
        );

        // Envoyer l'email de déblocage de fonds
        $this->sendFundsDisbursedNotification($application);
    }

    private function disburseFundsInternal(CreditApplication $application): void
    {
        $user = $this->userRepository->findOneBy(['email' => $application->getEmail()]);
        if (!$user) {
            throw new \Exception($this->translationService->tp('exceptions.user_not_found', [
                'email' => $application->getEmail()
            ], 'credit_workflow_service'));
        }

        // Trouver le compte principal de l'utilisateur
        $account = $this->accountRepository->findOneBy(['owner' => $user, 'type' => 'checking']);
        if (!$account) {
            throw new \Exception($this->translationService->tp('exceptions.main_account_not_found', [
                'email' => $user->getEmail()
            ], 'credit_workflow_service'));
        }

        // Trouver ou créer le sous-compte crédit
        $subAccountCredit = $account->getSubAccountCredit();
        if (!$subAccountCredit) {
            $subAccountCredit = new SubAccountCredit();
            $subAccountCredit->setAccount($account);
            $account->setSubAccountCredit($subAccountCredit);
            $this->entityManager->persist($subAccountCredit);
        }

        // Ajouter le montant du crédit au sous-compte crédit
        $subAccountCredit->credit((string) $application->getLoanAmount());

        // Créer une transaction pour traçabilité
        $transaction = new Transaction();
        $transaction->setAccount($account);
        $transaction->setType('credit');
        $transaction->setCategory('loan_payment'); // Ajout de la catégorie obligatoire
        $transaction->setAmount((string) $application->getLoanAmount());
        $transaction->setDescription($this->translationService->tp('transaction_descriptions.credit_release', [
            'contractNumber' => $this->contractGenerator->generateContractNumber($application)
        ], 'credit_workflow_service'));
        $transaction->setReference('CREDIT-' . $application->getId() . '-' . date('YmdHis'));
        $transaction->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($transaction);
        $this->entityManager->persist($subAccountCredit);
        $this->entityManager->flush();
    }

    private function sendDecisionEmail(CreditApplication $application, bool $approved): void
    {
        $preferredLocale = $this->resolveApplicationLocale($application);
        $message = new CreditDecisionEmailMessage(
            creditApplicationId: $application->getId(),
            customerEmail: $application->getEmail(),
            customerName: $application->getFirstName() . ' ' . $application->getLastName(),
            approved: $approved,
            loanAmount: $application->getLoanAmount(),
            contractNumber: $approved ? $this->contractGenerator->generateContractNumber($application) : null,
            preferredLocale: $preferredLocale
        );

        $this->messageBus->dispatch($message);
    }

    private function sendContractEmail(CreditApplication $application, string $contractPdf, string $filename): void
    {
        $preferredLocale = $this->resolveApplicationLocale($application);
        $message = new ContractEmailMessage(
            creditApplicationId: $application->getId(),
            customerEmail: $application->getEmail(),
            customerName: $application->getFirstName() . ' ' . $application->getLastName(),
            contractPdf: $contractPdf,
            contractFilename: $filename,
            contractNumber: $this->contractGenerator->generateContractNumber($application),
            preferredLocale: $preferredLocale
        );

        $this->messageBus->dispatch($message);
    }

    private function notifyAdminContractSigned(CreditApplication $application, ?string $signedContractPath): void
    {
        $preferredLocale = $this->resolveApplicationLocale($application);
        $message = new ContractSignedNotificationMessage(
            creditApplicationId: $application->getId(),
            customerName: $application->getFirstName() . ' ' . $application->getLastName(),
            customerEmail: $application->getEmail(),
            contractNumber: $this->contractGenerator->generateContractNumber($application),
            signedContractPath: $signedContractPath,
            adminEmail: $this->adminEmail,
            preferredLocale: $preferredLocale
        );

        $this->messageBus->dispatch($message);
    }

    private function saveContractFile(CreditApplication $application, string $contractPdf, string $filename): void
    {
        // Stocker dans public/uploads/contracts au lieu de var/contracts
        $contractsDir = $this->projectDir . '/public/uploads/contracts';
        if (!is_dir($contractsDir)){
            mkdir($contractsDir, 0755, true);
        }

        $filePath = $contractsDir . '/' . $filename;
        file_put_contents($filePath, $contractPdf);

        // Sauvegarder le chemin relatif dans l'entité (pour accès web)
        $relativePath = 'uploads/contracts/' . $filename;
        $application->setContractPath($relativePath);
        
        // Persister l'entité avec le nouveau chemin
        $this->entityManager->persist($application);
        $this->entityManager->flush();
    }

    /**
     * Récupère l'URL publique du contrat PDF
     */
    public function getContractUrl(CreditApplication $application): ?string
    {
        if (!$application->getContractPath()) {
            return null;
        }
        
        return '/' . $application->getContractPath();
    }

    /**
     * Vérifie si le fichier contrat existe physiquement
     */
    public function contractFileExists(CreditApplication $application): bool
    {
        if (!$application->getContractPath()) {
            return false;
        }
        
        $fullPath = $this->projectDir . '/public/' . $application->getContractPath();
        return file_exists($fullPath);
    }

    /**
     * Renvoie le contrat par email (régénère le contrat avec les informations actuelles)
     */
    public function sendContractByEmail(CreditApplication $application): void
    {
        // Incrémenter le compteur de renvois
        $application->incrementResendCount();
        $this->entityManager->persist($application);
        $this->entityManager->flush();

        // Régénérer le contrat avec les informations actuelles
        $this->generateAndSendContract($application);
    }

    private function resolveApplicationLocale(CreditApplication $application): string
    {
        // Priorité 1: Locale stockée dans la demande de crédit
        $locale = $application->getLocale();
        if ($locale) {
            return $locale;
        }

        // Priorité 2: Si l'utilisateur existe et a une langue définie
        $user = $this->userRepository->findOneBy(['email' => $application->getEmail()]);
        $locale = $user?->getLanguage();
        if ($locale) {
            return $locale;
        }

        // Priorité 3: Français par défaut
        return 'fr';
    }

    private function sendContractValidatedNotification(CreditApplication $application): void
    {
        $preferredLocale = $this->resolveApplicationLocale($application);
        $message = new ContractValidatedNotificationMessage(
            creditApplicationId: $application->getId(),
            customerEmail: $application->getEmail(),
            customerName: $application->getFirstName() . ' ' . $application->getLastName(),
            contractNumber: '', // Plus besoin, récupéré dans le handler
            loanAmount: (float)$application->getLoanAmount(),
            preferredLocale: $preferredLocale
        );

        $this->messageBus->dispatch($message);
    }

    private function sendFundsDisbursedNotification(CreditApplication $application): void
    {
        $preferredLocale = $this->resolveApplicationLocale($application);
        $message = new FundsDisbursedNotificationMessage(
            creditApplicationId: $application->getId(),
            customerEmail: $application->getEmail(),
            customerName: $application->getFirstName() . ' ' . $application->getLastName(),
            contractNumber: '', // Plus besoin, récupéré dans le handler
            loanAmount: (float)$application->getLoanAmount(),
            totalFees: $application->getTotalAppliedFees(),
            preferredLocale: $preferredLocale
        );

        $this->messageBus->dispatch($message);
    }

    private function sendContractSignedCustomerNotification(CreditApplication $application): void
    {
        $preferredLocale = $this->resolveApplicationLocale($application);
        $message = new ContractSignedCustomerNotificationMessage(
            creditApplicationId: $application->getId(),
            customerEmail: $application->getEmail(),
            customerName: $application->getFirstName() . ' ' . $application->getLastName(),
            loanAmount: (float)$application->getLoanAmount(),
            preferredLocale: $preferredLocale
        );

        $this->messageBus->dispatch($message);
    }
}
