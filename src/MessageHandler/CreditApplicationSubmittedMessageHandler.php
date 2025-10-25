<?php

namespace App\MessageHandler;

use App\Entity\CreditApplication;
use App\Message\CreditApplicationSubmittedMessage;
use App\Repository\UserRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreditApplicationSubmittedMessageHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EmailService $emailService,
        private readonly LoggerInterface $logger,
        private readonly UserRepository $userRepository,
        #[Autowire('%env(ADMIN_EMAIL)%')] private readonly string $adminEmail
    ) {}

    public function __invoke(CreditApplicationSubmittedMessage $message): void
    {
        $creditApplication = $this->entityManager->find(CreditApplication::class, $message->getCreditApplicationId());
        
        if (!$creditApplication) {
            $this->logger->error('Credit application not found', ['id' => $message->getCreditApplicationId()]);
            return;
        }

        try {
            // Utiliser la locale depuis le message (détectée depuis l'URL de la demande)
            $locale = $message->getLocale();
            
            // Si la locale n'est pas définie, utiliser fr par défaut
            if (empty($locale)) {
                $locale = 'fr';
            }
            
            $this->logger->info('Envoi des emails de demande de crédit', [
                'reference' => $creditApplication->getReferenceNumber(),
                'locale' => $locale,
                'email' => $creditApplication->getEmail()
            ]);
            
            // Email de confirmation au client
            $this->sendCustomerConfirmation($creditApplication, $locale);
            
            // Email de notification à l'admin
            $this->sendAdminNotification($creditApplication, $locale);
            
            $this->logger->info('Emails sent successfully for credit application', [
                'reference' => $creditApplication->getReferenceNumber(),
                'customer_email' => $creditApplication->getEmail()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send emails for credit application', [
                'reference' => $creditApplication->getReferenceNumber(),
                'error' => $e->getMessage()
            ]);
            throw $e; // Re-throw to retry the message
        }
    }

    private function sendCustomerConfirmation(CreditApplication $creditApplication, string $locale): void
    {
        // Nous devons injecter ProfessionalTranslationService pour traduire le sujet
        // Pour l'instant, utilisons une solution temporaire
        $this->emailService->sendEmail(
            to: $creditApplication->getEmail(),
            subject: $this->getTranslatedSubject($locale),
            template: 'email/credit_application/customer_confirmation.html.twig',
            context: [
                'creditApplication' => $creditApplication,
                'locale' => $locale,
            ],
            locale: $locale
        );
    }

    private function getTranslatedSubject(string $locale): string
    {
        // Traduction manuelle temporaire du sujet selon la locale
        return match($locale) {
            'fr' => 'Confirmation de votre demande de crédit',
            'de' => 'Bestätigung Ihres Kreditantrags',
            'nl' => 'Bevestiging van uw kredietaanvraag',
            'en' => 'Confirmation of your credit request',
            'es' => 'Confirmación de su solicitud de crédito',
            default => 'Confirmation de votre demande de crédit'
        };
    }

    private function sendAdminNotification(CreditApplication $creditApplication, string $locale): void
    {
        $this->emailService->sendEmail(
            to: $this->adminEmail,
            subject: $this->getTranslatedAdminSubject($creditApplication->getReferenceNumber(), $locale),
            template: 'email/credit_application/admin_notification.html.twig',
            context: [
                'creditApplication' => $creditApplication,
                'applicationId' => $creditApplication->getReferenceNumber(),
            ],
            locale: $locale
        );
    }

    private function getTranslatedAdminSubject(string $referenceNumber, string $locale): string
    {
        // Traduction manuelle temporaire du sujet selon la locale
        return match($locale) {
            'fr' => "Nouvelle demande de crédit - Réf: {$referenceNumber}",
            'de' => "Neuer Kreditantrag - Ref: {$referenceNumber}",
            'nl' => "Nieuwe kredietaanvraag - Ref: {$referenceNumber}",
            'en' => "New credit application - Ref: {$referenceNumber}",
            'es' => "Nueva solicitud de crédito - Ref: {$referenceNumber}",
            default => "Nouvelle demande de crédit - Réf: {$referenceNumber}"
        };
    }
}
