<?php

namespace App\MessageHandler;

use App\Message\InsuranceContractEmailMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\Repository\ContratAssuranceRepository;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;
use App\Service\ProfessionalTranslationService;
use App\Twig\CompanyVariablesExtension;

#[AsMessageHandler]
class InsuranceContractEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        #[Autowire('%app.mailer.from_email%')] private string $fromEmail,
        #[Autowire('%app.mailer.from_name%')] private string $fromName,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
        private ProfessionalTranslationService $translationService,
        private ContratAssuranceRepository $contratAssuranceRepository,
        private CompanyVariablesExtension $companyVariablesExtension
    ) {}

    public function __invoke(InsuranceContractEmailMessage $message): void
    {
        try {
            $locale = $message->preferredLocale ?? 'fr';
            
            // Récupérer le contrat d'assurance pour obtenir des informations additionnelles si nécessaire
            $contratAssurance = $this->contratAssuranceRepository->find($message->contratAssuranceId);
            if (!$contratAssurance) {
                $this->logger->error('ContratAssurance not found', ['id' => $message->contratAssuranceId]);
                return;
            }
            
            // Sauvegarder la locale actuelle et définir temporairement la locale du message
            $originalLocale = $this->translationService->getLocale();
            $this->translationService->setLocale($locale);
            
            $htmlContent = $this->twig->render('email/insurance_contract.html.twig', [
                'customerName' => $message->customerName,
                'contractNumber' => $message->contractNumber,
                'insuranceType' => $message->insuranceType,
                'monthlyPremium' => $message->monthlyPremium,
                'activationDate' => $message->activationDate,
                'status' => $message->status,
                'locale' => $locale,
                'translationService' => $this->translationService  // Injecter le service de traduction
            ]);

            // Traduire le type d'assurance pour l'objet de l'email
            $translatedInsuranceTypeForSubject = $this->translator->trans(
                $message->insuranceTypeKey, 
                [], 
                'enums', 
                $locale
            );
            
            $subject = $this->companyVariablesExtension->translateWithCompanyVariables(
                'email_insurance_contract.title', 
                ['insuranceType' => $translatedInsuranceTypeForSubject], 
                'email_insurance_contract', 
                $locale
            );

            $email = (new Email())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($message->customerEmail)
                ->subject($subject)
                ->html($htmlContent);

            // Ajout du PDF en pièce jointe
            $email->addPart(new DataPart(
                $message->contractPdf,
                $message->contractFilename,
                'application/pdf'
            ));

            $this->mailer->send($email);

            // Restaurer la locale originale
            $this->translationService->setLocale($originalLocale);

            $this->logger->info('Insurance contract email sent successfully', [
                'contractId' => $message->contratAssuranceId,
                'contractNumber' => $message->contractNumber,
                'customerEmail' => $message->customerEmail
            ]);

        } catch (\Exception $e) {
            // Restaurer la locale originale en cas d'erreur aussi
            if (isset($originalLocale)) {
                $this->translationService->setLocale($originalLocale);
            }
            
            $this->logger->error('Error sending insurance contract email', [
                'error' => $e->getMessage(),
                'contractNumber' => $message->contractNumber,
                'contractId' => $message->contratAssuranceId
            ]);
            throw $e;
        }
    }
}