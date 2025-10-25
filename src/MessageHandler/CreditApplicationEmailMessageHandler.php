<?php

namespace App\MessageHandler;

use App\Message\CreditApplicationEmailMessage;
use App\Service\EmailService;
use App\Service\CompanySettingsService;
use App\Trait\CompanyPlaceholderReplacerTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handler pour l'envoi d'emails de confirmation de demande de crédit
 */
#[AsMessageHandler]
class CreditApplicationEmailMessageHandler
{
    use CompanyPlaceholderReplacerTrait;

    public function __construct(
        private readonly EmailService $emailService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator
    ,
        private CompanySettingsService $companySettingsService) {}

    public function __invoke(CreditApplicationEmailMessage $message): void
    {
        try {
            // Générer l'URL de confirmation complète
            $confirmationUrl = $this->urlGenerator->generate(
                'credit_application_confirmation',
                ['hash' => $message->getConfirmationHash()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            // Mise à jour du contexte avec l'URL
            $context = $message->getContext();
            $context['confirmationUrl'] = $confirmationUrl;

            // Envoi de l'email
            $this->emailService->sendEmail(
                to: $message->getTo(),
                subject: $message->getSubject(),
                template: $message->getTemplate(),
                context: $context
            );

            $this->logger->info($this->translator->trans('email_credit_application.logs.email_sent', [], 'email_credit_application'), [
                'credit_application_id' => $message->getCreditApplicationId(),
                'customer_email' => $message->getCustomerEmail(),
                'loan_amount' => $message->getLoanAmount()
            ]);

        } catch (\Exception $e) {
            $this->logger->error($this->translator->trans('email_credit_application.logs.email_failed', [], 'email_credit_application'), [
                'credit_application_id' => $message->getCreditApplicationId(),
                'customer_email' => $message->getCustomerEmail(),
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
