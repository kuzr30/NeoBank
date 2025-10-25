<?php

namespace App\MessageHandler;

use App\Message\SendCreditTransferVerificationCodeMessage;
use App\Repository\UserRepository;
use App\Service\ProfessionalTranslationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
class SendCreditTransferVerificationCodeMessageHandler
{
    private MailerInterface $mailer;
    private Environment $twig;
    private LoggerInterface $logger;
    private string $fromEmail;
    private string $fromName;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        LoggerInterface $logger,
        string $fromEmail,
        string $fromName,
        private TranslatorInterface $translator,
        private ProfessionalTranslationService $translationService,
        private UserRepository $userRepository
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->logger = $logger;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    public function __invoke(SendCreditTransferVerificationCodeMessage $message): void
    {
        try {
            // Déterminer la locale de l'utilisateur
            $user = $this->userRepository->findOneBy(['email' => $message->getUserEmail()]);
            $locale = 'fr'; // Par défaut
            if ($user && $user->getLanguage()) {
                $supportedLocales = ['fr', 'nl', 'de', 'en', 'es'];
                if (in_array($user->getLanguage(), $supportedLocales, true)) {
                    $locale = $user->getLanguage();
                }
            }
            
            // Sauvegarder la locale actuelle et définir temporairement la locale du message
            $originalLocale = $this->translationService->getLocale();
            $this->translationService->setLocale($locale);
            
            $email = (new Email())
                ->from(sprintf('%s <%s>', $this->fromName, $this->fromEmail))
                ->to($message->getUserEmail())
                ->subject($this->translator->trans('email_credit_transfer_verification.title', [], 'email_credit_transfer_verification'))
                ->html($this->twig->render('email/credit_transfer_verification.html.twig', [
                    'userName' => $message->getUserName(),
                    'verificationCode' => $message->getVerificationCode(),
                    'amount' => $message->getAmount(),
                    'sourceAccount' => $message->getSourceAccount(),
                    'locale' => $locale
                ]));

            $this->mailer->send($email);

            // Restaurer la locale originale
            $this->translationService->setLocale($originalLocale);

            $this->logger->info($this->translator->trans('logs.success', [], 'email_credit_transfer_verification'), [
                'email' => $message->getUserEmail(),
                'amount' => $message->getAmount(),
            ]);

        } catch (\Exception $e) {
            // Restaurer la locale originale en cas d'erreur aussi
            if (isset($originalLocale)) {
                $this->translationService->setLocale($originalLocale);
            }
            
            $this->logger->error($this->translator->trans('logs.error', [], 'email_credit_transfer_verification'), [
                'error' => $e->getMessage(),
                'email' => $message->getUserEmail(),
                'amount' => $message->getAmount(),
            ]);

            throw $e;
        }
    }
}
