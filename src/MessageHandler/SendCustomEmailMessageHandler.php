<?php

namespace App\MessageHandler;

use App\Entity\CustomEmail;
use App\Message\SendCustomEmailMessage;
use App\Repository\CustomEmailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;

#[AsMessageHandler]
final readonly class SendCustomEmailMessageHandler
{
    public function __construct(
        private CustomEmailRepository $customEmailRepository,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        #[Autowire('%app.mailer.from_email%')] private string $fromEmail,
        #[Autowire('%app.mailer.from_name%')] private string $fromName,
        #[Autowire('%uploads_directory%')] private string $uploadsDirectory,
    ) {
    }

    public function __invoke(SendCustomEmailMessage $message): void
    {
        $customEmail = $this->customEmailRepository->find($message->getCustomEmailId());

        if (!$customEmail) {
            $this->logger->warning('CustomEmail not found', [
                'id' => $message->getCustomEmailId()
            ]);
            return;
        }

        // Check if already sent
        if ($customEmail->getStatus() === 'sent') {
            $this->logger->info('CustomEmail already sent', [
                'id' => $customEmail->getId()
            ]);
            return;
        }

        try {
            // Create and send email using Twig template with locale
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($customEmail->getRecipient()->getEmail())
                ->subject($customEmail->getSubject())
                ->htmlTemplate('email/custom_email.html.twig')
                ->context([
                    'customEmail' => $customEmail,
                ])
                ->locale($customEmail->getLocale() ?? 'fr');

            // Ajouter les pièces jointes
            $attachments = $customEmail->getAttachments();
            if ($attachments) {
                foreach ($attachments as $attachment) {
                    $filePath = $this->uploadsDirectory . '/' . $attachment;
                    if (file_exists($filePath)) {
                        $email->attachFromPath($filePath);
                    } else {
                        $this->logger->warning('Attachment file not found', [
                            'customEmailId' => $customEmail->getId(),
                            'filePath' => $filePath,
                        ]);
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
                'subject' => $customEmail->getSubject(),
            ]);

        } catch (\Exception $e) {
            // Mark as failed
            $customEmail->setStatus('failed');
            $customEmail->setErrorMessage($e->getMessage());
            $this->entityManager->flush();

            $this->logger->error('Failed to send CustomEmail', [
                'id' => $customEmail->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
    