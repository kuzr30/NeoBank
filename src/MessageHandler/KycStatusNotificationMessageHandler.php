<?php

namespace App\MessageHandler;

use App\Entity\KycSubmission;
use App\Message\KycStatusNotificationMessage;
use App\Service\EmailService;
use App\Service\ProfessionalTranslationService;
use App\Service\CompanySettingsService;
use App\Trait\CompanyPlaceholderReplacerTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class KycStatusNotificationMessageHandler
{
    use CompanyPlaceholderReplacerTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailService $emailService,
        private LoggerInterface $logger,
        private ProfessionalTranslationService $translationService,
        private CompanySettingsService $companySettingsService
    ) {
    }

    public function __invoke(KycStatusNotificationMessage $message): void
    {
        try {
            $submission = $this->entityManager->getRepository(KycSubmission::class)
                ->find($message->getKycSubmissionId());

            if (!$submission) {
                $this->logger->error($this->translationService->tp('logs.submission_not_found', [], 'email_kyc_approved'), [
                    'submission_id' => $message->getKycSubmissionId()
                ]);
                return;
            }

            $user = $submission->getUser();
            $status = $message->getStatus();

            // Utiliser la locale sauvegardée au moment de la soumission
            $userLocale = $submission->getSubmissionLocale() ?: 'fr';
            $originalLocale = $this->translationService->getLocale();
            $this->translationService->setLocale($userLocale);

            // Déterminer le template et le sujet selon le statut
            $template = match($status) {
                KycSubmission::STATUS_APPROVED => 'email/kyc_approved.html.twig',
                KycSubmission::STATUS_REJECTED => 'email/kyc_rejected.html.twig',
                default => null
            };

            $subject = match($status) {
                KycSubmission::STATUS_APPROVED => $this->translationService->tp('email_kyc_approved.title', [], 'email_kyc_approved'),
                KycSubmission::STATUS_REJECTED => $this->translationService->tp('email_kyc_rejected.title', [], 'email_kyc_rejected'),
                default => $this->translationService->tp('email_kyc_update.title', [], 'email_kyc_update')
            };

            if (!$template) {
                $this->logger->warning($this->translationService->tp('logs.template_not_found', [], 'email_kyc_approved'), [
                    'status' => $status,
                    'submission_id' => $submission->getId()
                ]);
                
                // Restaurer la locale originale
                $this->translationService->setLocale($originalLocale);
                return;
            }

            // Envoyer l'email
            $this->emailService->sendEmail(
                $user->getEmail(),
                $subject,
                $template,
                [
                    'user' => $user,
                    'submission' => $submission,
                    'status' => $status,
                    'translationService' => $this->translationService
                ],
                null, // toName
                null, // fromEmail
                null, // fromName
                $userLocale // locale
            );

            $this->logger->info($this->translationService->tp('logs.notification_sent', [], 'email_kyc_approved'), [
                'user_id' => $user->getId(),
                'submission_id' => $submission->getId(),
                'status' => $status,
                'email' => $user->getEmail()
            ]);

            // Restaurer la locale originale
            $this->translationService->setLocale($originalLocale);

        } catch (\Exception $e) {
            // Restaurer la locale en cas d'exception
            if (isset($originalLocale)) {
                $this->translationService->setLocale($originalLocale);
            }
            
            $this->logger->error($this->translationService->tp('logs.notification_error', [], 'email_kyc_approved'), [
                'submission_id' => $message->getKycSubmissionId(),
                'status' => $message->getStatus(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
