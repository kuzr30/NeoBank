<?php

namespace App\Service;

use App\Entity\CustomEmail;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class CustomEmailSender
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        #[Autowire('%app.mailer.from_email%')] private string $fromEmail,
        #[Autowire('%app.mailer.from_name%')] private string $fromName,
        #[Autowire('%uploads_directory%')] private string $uploadsDirectory,
    ) {
    }

    /**
     * Send a custom email immediately
     */
    public function send(CustomEmail $customEmail): bool
    {
        // Check if already sent
        if ($customEmail->getStatus() === 'sent') {
            $this->logger->info('CustomEmail already sent', [
                'id' => $customEmail->getId()
            ]);
            return false;
        }

        try {
            // Create and send email
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($customEmail->getRecipient()->getEmail())
                ->subject($customEmail->getSubject())
                ->html($customEmail->getMessage());

            // Ajouter les pièces jointes
            $attachments = $customEmail->getAttachments();
            if ($attachments) {
                foreach ($attachments as $attachment) {
                    $filePath = $this->uploadsDirectory . '/' . $attachment;
                    if (file_exists($filePath)) {
                        $email->attachFromPath($filePath);
                    }
                }
            }

            $this->mailer->send($email);

            // Mark as sent
            $customEmail->setStatus('sent');
            $customEmail->setSentAt(new \DateTime());
            $this->entityManager->flush();

            $this->logger->info('CustomEmail sent successfully', [
                'id' => $customEmail->getId(),
                'recipient' => $customEmail->getRecipient()->getEmail(),
            ]);

            return true;

        } catch (\Exception $e) {
            // Mark as failed
            $customEmail->setStatus('failed');
            $customEmail->setErrorMessage($e->getMessage());
            $this->entityManager->flush();

            $this->logger->error('Failed to send CustomEmail', [
                'id' => $customEmail->getId(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
