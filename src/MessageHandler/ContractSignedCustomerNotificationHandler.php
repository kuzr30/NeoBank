<?php

namespace App\MessageHandler;

use App\Message\ContractSignedCustomerNotificationMessage;
use App\Repository\CreditApplicationRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Psr\Log\LoggerInterface;
use App\Service\EmailService;
use App\Service\ProfessionalTranslationService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsMessageHandler]
class ContractSignedCustomerNotificationHandler
{
    public function __construct(
        private EmailService $emailService,
        private LoggerInterface $logger,
        private CreditApplicationRepository $creditApplicationRepository,
        private ProfessionalTranslationService $translationService,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function __invoke(ContractSignedCustomerNotificationMessage $message): void
    {
        try {
            $locale = $message->preferredLocale ?? 'fr';
            
            // Récupérer la CreditApplication pour obtenir le référence number
            $creditApplication = $this->creditApplicationRepository->find($message->creditApplicationId);
            if (!$creditApplication) {
                $this->logger->error('CreditApplication not found', ['id' => $message->creditApplicationId]);
                return;
            }
            
            // Générer l'URL de login avec le générateur d'URL Symfony
            $loginUrl = $this->urlGenerator->generate(
                'app_login',
                ['_locale' => $locale],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $templateData = [
                'customerName' => $message->customerName,
                'referenceNumber' => $creditApplication->getReferenceNumber(),
                'loanAmount' => $message->loanAmount,
                'creditApplicationId' => $message->creditApplicationId,
                'locale' => $locale,
                'loginUrl' => $loginUrl
            ];

            // Générer le sujet dans la langue appropriée
            $originalLocale = $this->translationService->getLocale();
            $this->translationService->setLocale($locale);
            
            $subject = $this->translationService->tp(
                'email_contract_signed_customer.subject',
                ['referenceNumber' => $creditApplication->getReferenceNumber()],
                'email_contract_signed_customer'
            );
            
            // Restaurer la locale originale
            $this->translationService->setLocale($originalLocale);

            $this->emailService->sendEmail(
                $message->customerEmail,
                $subject,
                'email/contract_signed_customer_notification.html.twig',
                $templateData,
                null,
                null,
                null,
                $locale
            );

            $this->logger->info('Contract signed customer notification email sent successfully', [
                'email' => $message->customerEmail,
                'referenceNumber' => $creditApplication->getReferenceNumber(),
                'locale' => $locale
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error sending contract signed customer notification email', [
                'error' => $e->getMessage(),
                'creditApplicationId' => $message->creditApplicationId
            ]);
            throw $e;
        }
    }
}