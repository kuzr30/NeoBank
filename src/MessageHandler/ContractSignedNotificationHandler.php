<?php

namespace App\MessageHandler;

use App\Message\ContractSignedNotificationMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Repository\CreditApplicationRepository;
use Psr\Log\LoggerInterface;
use App\Service\EmailService;

#[AsMessageHandler]
class ContractSignedNotificationHandler
{
    public function __construct(
        private EmailService $emailService,
        private LoggerInterface $logger,
        private CreditApplicationRepository $creditApplicationRepository
    ) {}

    public function __invoke(ContractSignedNotificationMessage $message): void
    {
        try {
            $locale = $message->preferredLocale ?? 'fr';
            
            // Récupérer la CreditApplication pour obtenir le référence number
            $creditApplication = $this->creditApplicationRepository->find($message->creditApplicationId);
            if (!$creditApplication) {
                $this->logger->error('CreditApplication not found', ['id' => $message->creditApplicationId]);
                return;
            }
            
            $templateData = [
                'customerName' => $message->customerName,
                'customerEmail' => $message->customerEmail,
                'referenceNumber' => $creditApplication->getReferenceNumber(),
                'creditApplicationId' => $message->creditApplicationId,
                'locale' => $locale
            ];

            // Si un contrat signé est fourni, l'envoyer en pièce jointe
            if ($message->signedContractPath && file_exists($message->signedContractPath)) {
                $attachmentContent = base64_encode(file_get_contents($message->signedContractPath));
                $attachmentFilename = 'contract_signed_' . $creditApplication->getReferenceNumber() . '.pdf';
                
                $this->emailService->sendEmailWithAttachment(
                    $message->adminEmail,
                    $this->getTranslatedSubject($locale, $creditApplication->getReferenceNumber()),
                    'email/contract_signed_notification.html.twig',
                    $templateData,
                    null,
                    null,
                    null,
                    $locale,
                    $attachmentContent,
                    $attachmentFilename,
                    'application/pdf'
                );
            } else {
                $this->emailService->sendEmail(
                    $message->adminEmail,
                    $this->getTranslatedSubject($locale, $creditApplication->getReferenceNumber()),
                    'email/contract_signed_notification.html.twig',
                    $templateData,
                    null,
                    null,
                    null,
                    $locale
                );
            }

        } catch (\Exception $e) {
            $this->logger->error('Error sending contract signed notification email', [
                'error' => $e->getMessage(),
                'creditApplicationId' => $message->creditApplicationId
            ]);
            throw $e;
        }
    }

    private function getTranslatedSubject(string $locale, string $referenceNumber): string
    {
        return match($locale) {
            'fr' => 'Notification de signature de contrat - ' . $referenceNumber,
            'de' => 'Benachrichtigung über Vertragsunterzeichnung - ' . $referenceNumber,
            'nl' => 'Melding van contractondertekening - ' . $referenceNumber,
            'en' => 'Contract signing notification - ' . $referenceNumber,
            'es' => 'Notificación de firma de contrato - ' . $referenceNumber,
            default => 'Notification de signature de contrat - ' . $referenceNumber
        };
    }
}
