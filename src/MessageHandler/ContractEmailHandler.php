<?php

namespace App\MessageHandler;

use App\Message\ContractEmailMessage;
use App\Repository\CreditApplicationRepository;
use App\Service\EmailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ContractEmailHandler
{
    public function __construct(
        private EmailService $emailService,
        private LoggerInterface $logger,
        private CreditApplicationRepository $creditApplicationRepository
    ) {}

    public function __invoke(ContractEmailMessage $message): void
    {
        $this->logger->info('🔥 ContractEmailHandler appelé', [
            'customerEmail' => $message->customerEmail,
            'creditApplicationId' => $message->creditApplicationId
        ]);
        
        try {
            $locale = $message->preferredLocale ?? 'fr';
            
            // Récupérer la CreditApplication pour obtenir le référence number
            $creditApplication = $this->creditApplicationRepository->find($message->creditApplicationId);
            if (!$creditApplication) {
                $this->logger->error('CreditApplication not found', ['id' => $message->creditApplicationId]);
                return;
            }
            
            $subject = $this->getTranslatedSubject($locale);

            // Utiliser EmailService pour créer l'email avec les bonnes variables d'entreprise
            $emailSent = $this->emailService->sendEmailWithAttachment(
                to: $message->customerEmail,
                subject: $subject,
                template: 'email/contract_ready.html.twig',
                context: [
                    'customerName' => $message->customerName,
                    'referenceNumber' => $creditApplication->getReferenceNumber(),
                    'locale' => $locale
                ],
                locale: $locale,
                attachmentContent: $message->contractPdf,
                attachmentFilename: $message->contractFilename,
                attachmentMimeType: 'application/pdf'
            );

            if ($emailSent) {
                $this->logger->info('Email de contrat envoyé', [
                    'customerEmail' => $message->customerEmail,
                    'referenceNumber' => $creditApplication->getReferenceNumber(),
                    'locale' => $locale,
                    'attachmentFilename' => $message->contractFilename
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email de contrat', [
                'error' => $e->getMessage(),
                'customerEmail' => $message->customerEmail,
                'contractNumber' => $message->contractNumber ?? 'N/A'
            ]);
            throw $e;
        }
    }

    private function getTranslatedSubject(string $locale): string
    {
        return match($locale) {
            'fr' => 'Votre contrat de crédit est prêt à signer',
            'de' => 'Ihr Kreditvertrag ist unterschriftsreif',
            'nl' => 'Uw kredietcontract is klaar om te ondertekenen',
            'en' => 'Your credit contract is ready to sign',
            'es' => 'Su contrato de crédito está listo para firmar',
            default => 'Votre contrat de crédit est prêt à signer'
        };
    }
}
