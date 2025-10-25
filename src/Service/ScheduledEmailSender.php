<?php

namespace App\Service;

use App\Entity\ScheduledEmail;
use App\Enum\EmailStatus;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class ScheduledEmailSender
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private EmailTemplateRenderer $templateRenderer,
        private LoggerInterface $logger,
        #[Autowire('%app.mailer.from_email%')] private string $fromEmail,
        #[Autowire('%app.mailer.from_name%')] private string $fromName,
    ) {
    }

    /**
     * Send a scheduled email immediately
     */
    public function send(ScheduledEmail $scheduledEmail): bool
    {
        // Check if already sent
        if ($scheduledEmail->getStatus() === EmailStatus::SENT) {
            $this->logger->info('ScheduledEmail already sent', [
                'id' => $scheduledEmail->getId()
            ]);
            return false;
        }

        try {
            // Generate email content using template renderer
            $emailContent = $this->templateRenderer->render($scheduledEmail);

            // Create and send email directly (content already has full HTML structure from base_email.html.twig)
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($scheduledEmail->getRecipient()->getEmail())
                ->subject($emailContent['subject'])
                ->html($emailContent['html']); // Direct HTML, no wrapper needed

            $this->mailer->send($email);

            // Mark as sent
            $scheduledEmail->markAsSent();
            $this->entityManager->flush();

            $this->logger->info('ScheduledEmail sent successfully', [
                'id' => $scheduledEmail->getId(),
                'recipient' => $scheduledEmail->getRecipient()->getEmail(),
                'template' => $scheduledEmail->getTemplateType()->value,
            ]);

            return true;

        } catch (\Exception $e) {
            // Mark as failed
            $scheduledEmail->markAsFailed($e->getMessage());
            $this->entityManager->flush();

            $this->logger->error('Failed to send ScheduledEmail', [
                'id' => $scheduledEmail->getId(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
