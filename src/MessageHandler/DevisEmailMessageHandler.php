<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\DemandeDevis;
use App\Message\DevisEmailMessage;
use App\Repository\DemandeDevisRepository;
use App\Service\EmailService;
use App\Service\CompanySettingsService;
use App\Trait\CompanyPlaceholderReplacerTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
class DevisEmailMessageHandler
{
    use CompanyPlaceholderReplacerTrait;

    public function __construct(
        private readonly EmailService $emailService,
        private readonly DemandeDevisRepository $demandeDevisRepository,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator,
        private readonly string $adminEmail,
        private readonly string $mailerFromEmail,
        private readonly string $mailerFromName
    ,
        private CompanySettingsService $companySettingsService) {}

    public function __invoke(DevisEmailMessage $message): void
    {
        try {
            $demande = $this->demandeDevisRepository->find($message->getDemandeDevisId());
            
            if (!$demande instanceof DemandeDevis) {
                $this->logger->error($this->translator->trans('logs.quote_not_found', [], 'email_devis_confirmation'), [
                    'demandeId' => $message->getDemandeDevisId()
                ]);
                return;
            }

            // Email de confirmation au client avec la locale appropriée
            $this->sendConfirmationEmail($demande, $message->getLocale());
            
            // Email de notification interne dans la même langue
            $this->sendNotificationEmail($demande, $message->getLocale());

            $this->logger->info($this->translator->trans('logs.emails_sent_success', [], 'email_devis_confirmation'), [
                'numeroDevis' => $demande->getNumeroDevis(),
                'email' => $demande->getEmail()
            ]);

        } catch (\Exception $e) {
            $this->logger->error($this->translator->trans('logs.emails_send_error', [], 'email_devis_confirmation'), [
                'error' => $e->getMessage(),
                'demandeId' => $message->getDemandeDevisId()
            ]);
            throw $e;
        }
    }

    private function sendConfirmationEmail(DemandeDevis $demande, string $locale = 'fr'): void
    {
        // Définir la locale pour l'email
        $originalLocale = $this->translator->getLocale();
        $this->translator->setLocale($locale);

        try {
            $subject = $this->translator->trans(
                'email_devis_confirmation.subject',
                ['company_name' => $this->mailerFromName],
                'email_devis_confirmation'
            );
                $subject = $this->replaceCompanyPlaceholders($subject);

            $success = $this->emailService->sendEmailAsync(
                $demande->getEmail(),
                $subject,
                'email/devis_confirmation.html.twig',
                [
                    'demande' => $demande,
                    '_locale' => $locale
                ],
                $demande->getNomComplet()
            );

            if (!$success) {
                throw new \RuntimeException($this->translator->trans('logs.confirmation_send_failed', [], 'email_devis_confirmation'));
            }
        } finally {
            // Restaurer la locale originale
            $this->translator->setLocale($originalLocale);
        }
    }

    private function sendNotificationEmail(DemandeDevis $demande, string $locale = 'fr'): void
    {
        // Définir la locale pour l'email admin
        $originalLocale = $this->translator->getLocale();
        $this->translator->setLocale($locale);

        try {
            // Traduire le type d'assurance pour l'objet de l'email
            $typeAssuranceTranslated = $this->translator->trans(
                $demande->getTypeAssurance()->getLabel(),
                [],
                'enums'
            );

            // Traduire l'objet de l'email
            $emailSubject = $this->translator->trans(
                'email_devis_notification.title',
                ['%typeAssurance%' => $typeAssuranceTranslated],
                'email_devis_notification'
            );

            $success = $this->emailService->sendEmailAsync(
                $this->mailerFromEmail, // Utiliser MAILER_FROM_EMAIL comme email admin
                $emailSubject,
                'email/devis_notification.html.twig',
                [
                    'demande' => $demande,
                    '_locale' => $locale
                ],
                sprintf('Administration %s', $this->mailerFromName)
            );

            if (!$success) {
                throw new \RuntimeException('Échec de l\'envoi de l\'email de notification admin');
            }
        } finally {
            // Restaurer la locale originale
            $this->translator->setLocale($originalLocale);
        }
    }
}
