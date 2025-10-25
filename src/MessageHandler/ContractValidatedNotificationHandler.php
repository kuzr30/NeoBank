<?php

namespace App\MessageHandler;

use App\Message\ContractValidatedNotificationMessage;
use App\Repository\CreditApplicationRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;
use App\Service\EmailService;

#[AsMessageHandler]
class ContractValidatedNotificationHandler
{
    public function __construct(
        private EmailService $emailService,
        private LoggerInterface $logger,
        private CreditApplicationRepository $creditApplicationRepository
    ) {}

    public function __invoke(ContractValidatedNotificationMessage $message): void
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
                'referenceNumber' => $creditApplication->getReferenceNumber(),
                'loanAmount' => $message->loanAmount,
                'creditApplicationId' => $message->creditApplicationId,
                'locale' => $locale
            ];

            $subject = $this->getTranslatedSubject($locale, $creditApplication->getReferenceNumber());

            $this->emailService->sendEmail(
                $message->customerEmail,
                $subject,
                'email/contract_validated_notification.html.twig',
                $templateData,
                null,
                null,
                null,
                $locale
            );

            $this->logger->info('Contract validation email sent successfully', [
                'email' => $message->customerEmail,
                'referenceNumber' => $creditApplication->getReferenceNumber(),
                'locale' => $locale
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error sending contract validation email', [
                'error' => $e->getMessage(),
                'creditApplicationId' => $message->creditApplicationId
            ]);
            throw $e;
        }
    }

    private function getTranslatedSubject(string $locale, string $referenceNumber): string
    {
        return match($locale) {
            'fr' => 'Validation de contrat - ' . $referenceNumber,
            'de' => 'Vertragsvalidierung - ' . $referenceNumber,
            'nl' => 'Contractvalidatie - ' . $referenceNumber,
            'en' => 'Contract validation - ' . $referenceNumber,
            'es' => 'Validación de contrato - ' . $referenceNumber,
            default => 'Validation de contrat - ' . $referenceNumber
        };
    }
}
