<?php

namespace App\MessageHandler;

use App\Message\CreditApplicationAdminNotificationMessage;
use App\Service\EmailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handler pour les notifications admin de nouvelles demandes de crédit
 */
#[AsMessageHandler]
class CreditApplicationAdminNotificationMessageHandler
{
    public function __construct(
        private readonly EmailService $emailService,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator
    ) {}

    public function __invoke(CreditApplicationAdminNotificationMessage $message): void
    {
        try {
            $this->emailService->sendEmail(
                to: $message->getTo(),
                subject: $message->getSubject(),
                template: $message->getTemplate(),
                context: $message->getContext()
            );

            $this->logger->info($this->translator->trans('email_credit_application_admin.logs.admin_notification_sent', [], 'email_credit_application_admin'), [
                'credit_application_id' => $message->getCreditApplicationId(),
                'customer_name' => $message->getCustomerName(),
                'loan_amount' => $message->getLoanAmount()
            ]);

        } catch (\Exception $e) {
            $this->logger->error($this->translator->trans('email_credit_application_admin.logs.email_failed', [], 'email_credit_application_admin'), [
                'credit_application_id' => $message->getCreditApplicationId(),
                'customer_name' => $message->getCustomerName(),
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
