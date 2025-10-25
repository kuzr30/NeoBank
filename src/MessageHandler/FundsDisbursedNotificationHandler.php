<?php

namespace App\MessageHandler;

use App\Message\FundsDisbursedNotificationMessage;
use App\Repository\CreditApplicationRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;
use App\Service\EmailService;

#[AsMessageHandler]
class FundsDisbursedNotificationHandler
{
    public function __construct(
        private EmailService $emailService,
        private LoggerInterface $logger,
        private CreditApplicationRepository $creditApplicationRepository
    ) {}

    public function __invoke(FundsDisbursedNotificationMessage $message): void
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
                'totalFees' => $message->totalFees,
                'creditApplicationId' => $message->creditApplicationId,
                'locale' => $locale
            ];

            $subject = $this->getTranslatedSubject($locale, $creditApplication->getReferenceNumber(), $message->loanAmount);

            $this->emailService->sendEmail(
                $message->customerEmail,
                $subject,
                'email/funds_disbursed_notification.html.twig',
                $templateData,
                null,
                null,
                null,
                $locale
            );

            $this->logger->info('Funds disbursed email sent successfully', [
                'email' => $message->customerEmail,
                'referenceNumber' => $creditApplication->getReferenceNumber(),
                'amount' => $message->loanAmount,
                'locale' => $locale
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error sending funds disbursed email', [
                'error' => $e->getMessage(),
                'creditApplicationId' => $message->creditApplicationId
            ]);
            throw $e;
        }
    }

    private function getTranslatedSubject(string $locale, string $referenceNumber, float $loanAmount): string
    {
        $formattedAmount = number_format($loanAmount, 2) . '€';
        
        return match($locale) {
            'fr' => 'Déblocage des fonds - ' . $referenceNumber . ' - ' . $formattedAmount,
            'de' => 'Freigabe der Mittel - ' . $referenceNumber . ' - ' . $formattedAmount,
            'nl' => 'Vrijgave van fondsen - ' . $referenceNumber . ' - ' . $formattedAmount,
            'en' => 'Funds disbursement - ' . $referenceNumber . ' - ' . $formattedAmount,
            'es' => 'Desembolso de fondos - ' . $referenceNumber . ' - ' . $formattedAmount,
            default => 'Déblocage des fonds - ' . $referenceNumber . ' - ' . $formattedAmount
        };
    }
}
