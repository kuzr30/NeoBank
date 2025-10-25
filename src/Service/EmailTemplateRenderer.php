<?php

namespace App\Service;

use App\Entity\ScheduledEmail;
use App\Enum\AccountIncompleteReason;
use App\Enum\CreditApplicationIncompleteReason;
use App\Enum\EmailTemplateType;
use App\Enum\KycRejectionReason;
use App\Repository\PaymentAccountRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class EmailTemplateRenderer
{
    public function __construct(
        private Environment $twig,
        private TranslatorInterface $translator,
        private ProfessionalTranslationService $translationService,
        private UrlGeneratorInterface $urlGenerator,
        private PaymentAccountRepository $paymentAccountRepository,
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
        #[Autowire('%app.url%')] private string $frontendUrl,
    ) {
    }

    /**
     * Render email content for a scheduled email
     *
     * @return array{subject: string, html: string}
     */
    public function render(ScheduledEmail $scheduledEmail): array
    {
        $locale = $scheduledEmail->getLocale();
        $templateType = $scheduledEmail->getTemplateType();
        $recipient = $scheduledEmail->getRecipient();

        // Force la locale pour le rendu de l'email (définie par l'admin)
        $previousLocale = $this->translationService->getLocale();
        $this->translationService->setLocale($locale);

        // Générer l'URL de connexion avec la bonne locale et la bonne traduction de route
        $loginUrl = $this->urlGenerator->generate('app_login', [
            '_locale' => $locale
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        // Build template variables
        $variables = [
            'name' => $recipient->getFirstname() ?? $recipient->getEmail(),
            'loginUrl' => $loginUrl,
            'customMessage' => $scheduledEmail->getCustomMessage(),
            'locale' => $locale,
            '_locale' => $locale, // Force locale for Twig translations
            'translationService' => $this->translationService, // Ajouter le service de traduction pour le template de base
        ];

        // Add amount and payment account for payment details template
        if ($templateType === EmailTemplateType::PAYMENT_DETAILS) {
            $variables['amount'] = $scheduledEmail->getAmount() ?? '0.00';
            
            // Get the first active payment account (you can modify this logic if needed)
            $paymentAccount = $this->paymentAccountRepository->findOneBy([], ['id' => 'DESC']);
            if ($paymentAccount) {
                $variables['paymentAccount'] = $paymentAccount;
            }
        }

        // Add rejection/incomplete reasons if applicable
        if ($templateType->requiresReasons() && $scheduledEmail->getReasons()) {
            $reasons = [];
            $translationDomain = $this->getReasonTranslationDomain($templateType);
            
            foreach ($scheduledEmail->getReasons() as $reasonValue) {
                try {
                    $reason = $this->getReasonEnum($templateType, $reasonValue);
                    if ($reason) {
                        $reasons[] = [
                            'label' => $this->translator->trans($reason->getTranslationKey(), [], $translationDomain, $locale),
                            'description' => $this->translator->trans($reason->getTranslationKey() . '_description', [], $translationDomain, $locale),
                        ];
                    }
                } catch (\ValueError|\Error $e) {
                    // Skip invalid reason (wrong enum type for this template)
                    continue;
                }
            }
            
            if (!empty($reasons)) {
                $variables['reasons'] = $reasons;
            }
        }

        // Get template path
        $templatePath = $this->getTemplatePath($templateType);

        // Render HTML content with forced locale
        $html = $this->twig->render($templatePath, $variables);

        // Generate subject
        $subject = $this->getSubject($templateType, $locale, $scheduledEmail);

        // Restore la locale précédente
        $this->translationService->setLocale($previousLocale);

        return [
            'subject' => $subject,
            'html' => $html,
        ];
    }

    private function getTemplatePath(EmailTemplateType $templateType): string
    {
        return match ($templateType) {
            EmailTemplateType::KYC_REJECTED => 'email/scheduled/kyc_rejected.html.twig',
            EmailTemplateType::INCOMPLETE_ACCOUNT => 'email/scheduled/incomplete_account.html.twig',
            EmailTemplateType::FEES_INQUIRY => 'email/scheduled/fees_inquiry.html.twig',
            EmailTemplateType::ACCOUNT_ACTIVATION_REMINDER => 'email/scheduled/account_activation_reminder.html.twig',
            EmailTemplateType::CREDIT_APPLICATION_INCOMPLETE => 'email/scheduled/credit_application_incomplete.html.twig',
            EmailTemplateType::ACCOUNT_CREATION_FOLLOW_UP => 'email/scheduled/account_creation_follow_up.html.twig',
            EmailTemplateType::PAYMENT_DETAILS => 'email/payment_details.html.twig',
        };
    }

    private function getSubject(EmailTemplateType $templateType, string $locale, ScheduledEmail $scheduledEmail): string
    {
        $translationKey = 'email_template.subject.' . $templateType->value;
        
        // For payment details, we need to replace %amount% placeholder
        $subject = $this->translator->trans($translationKey, [], 'admin', $locale);
        
        // Replace amount placeholder for payment details
        if ($templateType === EmailTemplateType::PAYMENT_DETAILS) {
            $amount = $scheduledEmail->getAmount() ?? '0.00';
            $subject = str_replace('%amount%', $amount, $subject);
        }
        
        return $subject;
    }

    private function getReasonEnum(EmailTemplateType $templateType, string $reasonValue): KycRejectionReason|AccountIncompleteReason|CreditApplicationIncompleteReason|null
    {
        return match($templateType) {
            EmailTemplateType::KYC_REJECTED => KycRejectionReason::from($reasonValue),
            EmailTemplateType::INCOMPLETE_ACCOUNT => AccountIncompleteReason::from($reasonValue),
            EmailTemplateType::CREDIT_APPLICATION_INCOMPLETE => CreditApplicationIncompleteReason::from($reasonValue),
            default => null,
        };
    }

    private function getReasonTranslationDomain(EmailTemplateType $templateType): string
    {
        return match($templateType) {
            EmailTemplateType::KYC_REJECTED => 'kyc_rejection_reasons',
            EmailTemplateType::INCOMPLETE_ACCOUNT => 'account_incomplete_reasons',
            EmailTemplateType::CREDIT_APPLICATION_INCOMPLETE => 'credit_application_incomplete_reasons',
            default => 'messages',
        };
    }
}
