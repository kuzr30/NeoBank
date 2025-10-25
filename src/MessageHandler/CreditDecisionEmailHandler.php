<?php

namespace App\MessageHandler;

use App\Message\CreditDecisionEmailMessage;
use App\Repository\CreditApplicationRepository;
use App\Service\EmailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreditDecisionEmailHandler
{
    public function __construct(
        private EmailService $emailService,
        private LoggerInterface $logger,
        private CreditApplicationRepository $creditApplicationRepository
    ) {}

    public function __invoke(CreditDecisionEmailMessage $message): void
    {
        $this->logger->info('🔥 CreditDecisionEmailHandler appelé', [
            'customerEmail' => $message->customerEmail,
            'approved' => $message->approved,
            'creditApplicationId' => $message->creditApplicationId
        ]);
        
        try {
            $locale = $message->preferredLocale ?? 'fr';
            
            // Récupérer la CreditApplication pour obtenir le référence number
            $creditApplication = $this->creditApplicationRepository->find($message->creditApplicationId);
            $referenceNumber = $creditApplication ? $creditApplication->getReferenceNumber() : null;
            
            $subject = $this->getTranslatedSubject($message->approved, $locale);
            $template = $message->approved 
                ? 'email/credit_approved.html.twig'
                : 'email/credit_rejected.html.twig';

            $this->logger->info('💫 Avant appel EmailService::sendEmail', [
                'subject' => $subject,
                'template' => $template,
                'to' => $message->customerEmail
            ]);

            $this->emailService->sendEmail(
                to: $message->customerEmail,
                subject: $subject,
                template: $template,
                context: [
                    'customerName' => $message->customerName,
                    'loanAmount' => $message->loanAmount,
                    'referenceNumber' => $referenceNumber,
                    'approved' => $message->approved,
                    'locale' => $locale
                ],
                locale: $locale
            );

            $this->logger->info('Email de décision de crédit envoyé', [
                'customerEmail' => $message->customerEmail,
                'approved' => $message->approved,
                'referenceNumber' => $referenceNumber,
                'locale' => $locale
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email de décision de crédit', [
                'error' => $e->getMessage(),
                'customerEmail' => $message->customerEmail,
                'approved' => $message->approved
            ]);
            throw $e;
        }
    }

    private function getTranslatedSubject(bool $approved, string $locale): string
    {
        if ($approved) {
            return match($locale) {
                'fr' => 'Félicitations ! Votre crédit a été approuvé',
                'de' => 'Herzlichen Glückwunsch! Ihr Kredit wurde genehmigt',
                'nl' => 'Gefeliciteerd! Uw krediet is goedgekeurd',
                'en' => 'Congratulations! Your credit has been approved',
                'es' => '¡Felicidades! Su crédito ha sido aprobado',
                default => 'Félicitations ! Votre crédit a été approuvé'
            };
        } else {
            return match($locale) {
                'fr' => 'Information concernant votre demande de crédit',
                'de' => 'Informationen zu Ihrem Kreditantrag',
                'nl' => 'Informatie betreffende uw kredietaanvraag',
                'en' => 'Information regarding your credit application',
                'es' => 'Información sobre su solicitud de crédito',
                default => 'Information concernant votre demande de crédit'
            };
        }
    }
}
