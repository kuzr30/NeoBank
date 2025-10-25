<?php

namespace App\Controller\Admin;

use App\Entity\ScheduledEmail;
use App\Entity\User;
use App\Enum\AccountIncompleteReason;
use App\Enum\CreditApplicationIncompleteReason;
use App\Enum\EmailStatus;
use App\Enum\EmailTemplateType;
use App\Enum\KycRejectionReason;
use App\Service\ScheduledEmailSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/scheduled-email')]
class SendScheduledEmailController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ScheduledEmailSender $emailSender,
    ) {
    }

    #[Route('/send', name: 'admin_send_scheduled_email')]
    public function send(Request $request): Response
    {
        $templateType = $request->query->get('templateType');
        if ($templateType) {
            $templateType = EmailTemplateType::from($templateType);
        }

        return $this->render('admin/scheduled_email/send.html.twig', [
            'templateType' => $templateType,
            'templateTypes' => EmailTemplateType::cases(),
            'users' => $this->entityManager->getRepository(User::class)->findAll(),
        ]);
    }

    #[Route('/submit', name: 'admin_submit_scheduled_email', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        $templateType = EmailTemplateType::from($request->request->get('templateType'));
        $recipientId = $request->request->get('recipient');
        $locale = $request->request->get('locale');
        $reasons = $request->request->all()['reasons'] ?? [];
        $customMessage = $request->request->get('customMessage');
        $amount = $request->request->get('amount');

        $recipient = $this->entityManager->getRepository(User::class)->find($recipientId);
        if (!$recipient) {
            $this->addFlash('error', 'Destinataire introuvable.');
            return $this->redirectToRoute('admin_send_scheduled_email');
        }

        $scheduledEmail = new ScheduledEmail();
        $scheduledEmail->setTemplateType($templateType);
        $scheduledEmail->setRecipient($recipient);
        $scheduledEmail->setLocale($locale);
        $scheduledEmail->setReasons($reasons);
        $scheduledEmail->setCustomMessage($customMessage);
        $scheduledEmail->setStatus(EmailStatus::PENDING);
        $scheduledEmail->setCreatedBy($this->getUser());
        
        // Set amount for payment details template
        if ($templateType === EmailTemplateType::PAYMENT_DETAILS && $amount) {
            $scheduledEmail->setAmount($amount);
        }

        $this->entityManager->persist($scheduledEmail);
        $this->entityManager->flush();

        // Send email immediately
        $sent = $this->emailSender->send($scheduledEmail);
        
        if ($sent) {
            $this->addFlash('success', 'Email envoyé avec succès à ' . $recipient->getEmail());
        } else {
            $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email. Consultez les logs pour plus de détails.');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/reasons/{templateType}', name: 'admin_get_reasons')]
    public function getReasons(string $templateType): Response
    {
        $type = EmailTemplateType::from($templateType);
        $reasons = $this->getReasonsForTemplate($type);

        return $this->render('admin/scheduled_email/_reasons.html.twig', [
            'reasons' => $reasons,
            'templateType' => $type,
        ]);
    }

    private function getReasonsForTemplate(EmailTemplateType $templateType): array
    {
        return match($templateType) {
            EmailTemplateType::KYC_REJECTED => $this->formatReasons(KycRejectionReason::cases()),
            EmailTemplateType::INCOMPLETE_ACCOUNT => $this->formatReasons(AccountIncompleteReason::cases()),
            EmailTemplateType::CREDIT_APPLICATION_INCOMPLETE => $this->formatReasons(CreditApplicationIncompleteReason::cases()),
            default => [],
        };
    }

    private function formatReasons(array $reasons): array
    {
        return array_map(fn($reason) => [
            'value' => $reason->value,
            'label' => $reason->getLabel(),
            'description' => $reason->getDescription(),
        ], $reasons);
    }
}
