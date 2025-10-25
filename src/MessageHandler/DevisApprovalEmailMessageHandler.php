<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\DemandeDevis;
use App\Message\DevisApprovalEmailMessage;
use App\Repository\DemandeDevisRepository;
use App\Service\EmailService;
use App\Service\CompanySettingsService;
use App\Trait\CompanyPlaceholderReplacerTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
class DevisApprovalEmailMessageHandler
{
    use CompanyPlaceholderReplacerTrait;

    public function __construct(
        private readonly EmailService $emailService,
        private readonly DemandeDevisRepository $demandeDevisRepository,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator,
        private readonly string $mailerFromEmail,
        private readonly string $mailerFromName
    ,
        private CompanySettingsService $companySettingsService) {}

    public function __invoke(DevisApprovalEmailMessage $message): void
    {
        try {
            $demande = $this->demandeDevisRepository->find($message->getDemandeDevisId());
            
            if (!$demande instanceof DemandeDevis) {
                $this->logger->error('Demande de devis non trouvée pour l\'envoi d\'email d\'approbation', [
                    'demandeId' => $message->getDemandeDevisId()
                ]);
                return;
            }

            // Email au client
            $this->sendApprovalEmail($demande, $message->getLocale());

            $this->logger->info('Email d\'approbation envoyé avec succès', [
                'numeroDevis' => $demande->getNumeroDevis(),
                'email' => $demande->getEmail()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email d\'approbation', [
                'error' => $e->getMessage(),
                'demandeId' => $message->getDemandeDevisId()
            ]);
            throw $e;
        }
    }

    private function sendApprovalEmail(DemandeDevis $demande, string $locale = 'fr'): void
    {
        // Définir la locale pour l'email
        $originalLocale = $this->translator->getLocale();
        $this->translator->setLocale($locale);

        try {
            $subject = $this->translator->trans(
                'email_devis_approval.subject',
                ['%numeroDevis%' => $demande->getNumeroDevis()],
                'email_devis_approval'
            );
                $subject = $this->replaceCompanyPlaceholders($subject);

            $success = $this->emailService->sendEmailAsync(
                $demande->getEmail(),
                $subject,
                'email/devis_approval.html.twig',
                [
                    'demande' => $demande,
                    '_locale' => $locale
                ],
                $demande->getNomComplet(),
                $this->mailerFromEmail,
                $this->mailerFromName,
                $locale  // Passer la locale au service EmailService
            );

            if (!$success) {
                throw new \RuntimeException('Échec de l\'envoi de l\'email d\'approbation');
            }
        } finally {
            // Restaurer la locale originale
            $this->translator->setLocale($originalLocale);
        }
    }
}