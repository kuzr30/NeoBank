<?php

namespace App\MessageHandler;

use App\Message\NewKycSubmissionMessage;
use App\Repository\KycSubmissionRepository;
use App\Repository\UserRepository;
use App\Service\ProfessionalTranslationService;
use App\Service\CompanySettingsService;
use App\Trait\CompanyPlaceholderReplacerTrait;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class NewKycSubmissionMessageHandler
{
    use CompanyPlaceholderReplacerTrait;

    public function __construct(
        private KycSubmissionRepository $kycSubmissionRepository,
        private UserRepository $userRepository,
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
        private ProfessionalTranslationService $translationService,
        private LoggerInterface $logger,
        private CompanySettingsService $companySettingsService,
        #[Autowire('%env(MAILER_FROM_EMAIL)%')] private string $fromEmail,
        #[Autowire('%env(MAILER_FROM_NAME)%')] private string $fromName
    ) {
    }

    public function __invoke(NewKycSubmissionMessage $message): void
    {
        try {
            $submission = $this->kycSubmissionRepository->find($message->getSubmissionId());
            if (!$submission) {
                $this->logger->error('KYC submission not found', ['submission_id' => $message->getSubmissionId()]);
                return;
            }

            $user = $submission->getUser();
            
            // Déterminer la locale de l'utilisateur
            $userLocale = 'fr'; // Par défaut
            if ($user && $user->getLanguage()) {
                $supportedLocales = ['fr', 'nl', 'de', 'en', 'es'];
                if (in_array($user->getLanguage(), $supportedLocales, true)) {
                    $userLocale = $user->getLanguage();
                }
            }
            
            // Sauvegarder la locale actuelle
            $originalLocale = $this->translationService->getLocale();
            $this->translationService->setLocale($userLocale);

            // Email à l'utilisateur avec sa locale
            $userSubject = $this->translator->trans('email_kyc_submission_confirmation.title', [], 'email_kyc_submission_confirmation', $userLocale);
            $userSubject = $this->replaceCompanyPlaceholders($userSubject);
            $userEmail = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($user->getEmail())
                ->subject($userSubject)
                ->htmlTemplate('email/kyc_submission_confirmation.html.twig')
                ->context([
                    'user' => $user,
                    'submission' => $submission,
                    'translationService' => $this->translationService
                ]);

            $this->mailer->send($userEmail);

            $this->logger->info('KYC submission confirmation email sent', [
                'user_id' => $user->getId(),
                'submission_id' => $submission->getId(),
                'locale' => $userLocale
            ]);

            // Restaurer la locale par défaut pour les emails admin
            $this->translationService->setLocale('fr');

            // Email aux administrateurs (toujours en français)
            $admins = $this->userRepository->findByRole('ROLE_ADMIN');
            foreach ($admins as $admin) {
                $adminSubject = $this->translator->trans('email_kyc_submission_admin.title', [], 'email_kyc_submission_admin', 'fr');
                $adminSubject = $this->replaceCompanyPlaceholders($adminSubject);
                $adminEmail = (new TemplatedEmail())
                    ->from(new Address($this->fromEmail, $this->fromName))
                    ->to($admin->getEmail())
                    ->subject($adminSubject)
                    ->htmlTemplate('email/kyc_submission_admin_notification.html.twig')
                    ->context([
                        'admin' => $admin,
                        'user' => $user,
                        'submission' => $submission,
                        'translationService' => $this->translationService
                    ]);

                $this->mailer->send($adminEmail);
            }

            // Restaurer la locale originale
            $this->translationService->setLocale($originalLocale);

        } catch (\Exception $e) {
            // Restaurer la locale en cas d'erreur
            if (isset($originalLocale)) {
                $this->translationService->setLocale($originalLocale);
            }
            
            $this->logger->error('Error sending KYC submission emails', [
                'submission_id' => $message->getSubmissionId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
