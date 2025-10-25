<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Account;
use App\Entity\CardOpposition;
use App\Entity\CardSubscription;
use App\Message\AccountCreatedNotificationMessage;
use App\Service\ProfessionalTranslationService;
use Symfony\Component\Messenger\MessageBusInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
        private ProfessionalTranslationService $translationService,
        private ParameterBagInterface $params,
        private MailerInterface $mailer,
        private string $defaultFromEmail
    ) {
    }

    /**
     * Notifie l'utilisateur de la création de son compte via message async
     */
    public function sendAccountCreatedNotification(User $user, Account $account): void
    {
        try {
            // Récupérer la locale de soumission KYC de l'utilisateur
            $submissionLocale = $this->getUserSubmissionLocale($user);
            
            // Récupérer le code du pays depuis l'IBAN
            $countryCode = $this->getCountryCodeFromIban($account->getIban());
            
            $message = new AccountCreatedNotificationMessage(
                to: $user->getEmail(),
                userFirstName: $user->getFirstName(),
                accountNumber: $account->getAccountNumber(),
                iban: $account->getIban(),
                countryCode: $countryCode,
                toName: $user->getFirstName() . ' ' . $user->getLastName(),
                locale: $submissionLocale
            );

            $this->messageBus->dispatch($message);

            $this->logger->info('Message de création de compte envoyé dans la queue', [
                'user_id' => $user->getId(),
                'account_id' => $account->getId(),
                'email' => $user->getEmail(),
                'submission_locale' => $submissionLocale
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise en queue du message de création de compte', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            // Ne pas lever d'exception, juste logger l'erreur
        }
    }

    /**
     * Notifie l'utilisateur de la suspension de ses comptes
     */
    public function sendAccountSuspendedNotification(User $user): void
    {
        // TODO: Implémenter avec le système de messaging
        $this->logger->info('TODO: Implémentation de la suspension de compte avec messaging', [
            'user_id' => $user->getId()
        ]);
    }

    private function getUserSubmissionLocale(User $user): string
    {
        // Récupérer la locale de soumission KYC de l'utilisateur
        $kycSubmission = $user->getKycSubmission();
        if ($kycSubmission && $kycSubmission->getSubmissionLocale()) {
            return $kycSubmission->getSubmissionLocale();
        }
        
        // Fallback sur la langue de l'utilisateur ou français par défaut
        return $user->getLanguage() ?? 'fr';
    }

    private function getCountryNameFromIban(string $iban, string $locale): string
    {
        $countryCode = substr($iban, 0, 2);
        $countryKey = match($countryCode) {
            'FR' => 'countries.FR',
            'BE' => 'countries.BE',
            'NL' => 'countries.NL',
            'DE' => 'countries.DE',
            'ES' => 'countries.ES',
            'IT' => 'countries.IT',
            'LU' => 'countries.LU',
            default => 'countries.OTHER'
        };
        
        // Traduire selon la locale de soumission
        $originalLocale = $this->translationService->getLocale();
        $this->translationService->setLocale($locale);
        
        $translatedCountry = $this->translationService->tp($countryKey, [], 'email_notification_created_account');
        
        // Restaurer la locale originale
        $this->translationService->setLocale($originalLocale);
        
        return $translatedCountry;
    }

    /**
     * Récupère le code pays depuis un IBAN
     */
    private function getCountryCodeFromIban(string $iban): string
    {
        return substr($iban, 0, 2);
    }

    /**
     * Notification de création de souscription
     */
    public function notifySubscriptionCreated(CardSubscription $subscription): void
    {
        try {
            $email = (new Email())
                ->from($this->defaultFromEmail)
                ->to($subscription->getUser()->getEmail())
                ->subject('Demande de carte enregistrée - Ref: ' . $subscription->getReference())
                ->html($this->buildSubscriptionCreatedTemplate($subscription));

            $this->mailer->send($email);
            
            $this->logger->info('Email de confirmation souscription envoyé', [
                'subscription_id' => $subscription->getId(),
                'user_email' => $subscription->getUser()->getEmail()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email souscription', [
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notification d'approbation de souscription
     */
    public function notifySubscriptionApproved(CardSubscription $subscription): void
    {
        try {
            $email = (new Email())
                ->from($this->defaultFromEmail)
                ->to($subscription->getUser()->getEmail())
                ->subject('Votre carte a été approuvée - Ref: ' . $subscription->getReference())
                ->html($this->buildSubscriptionApprovedTemplate($subscription));

            $this->mailer->send($email);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email approbation', [
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notification de rejet de souscription
     */
    public function notifySubscriptionRejected(CardSubscription $subscription): void
    {
        try {
            $email = (new Email())
                ->from($this->defaultFromEmail)
                ->to($subscription->getUser()->getEmail())
                ->subject('Votre demande de carte - Ref: ' . $subscription->getReference())
                ->html($this->buildSubscriptionRejectedTemplate($subscription));

            $this->mailer->send($email);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email rejet', [
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notification de création d'opposition
     */
    public function notifyOppositionCreated(CardOpposition $opposition): void
    {
        try {
            $email = (new Email())
                ->from($this->defaultFromEmail)
                ->to($opposition->getRequestedBy()->getEmail())
                ->subject('Opposition enregistrée - Carte bloquée - Ref: ' . $opposition->getReference())
                ->html($this->buildOppositionCreatedTemplate($opposition));

            $this->mailer->send($email);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email opposition', [
                'opposition_id' => $opposition->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notification de traitement d'opposition
     */
    public function notifyOppositionProcessed(CardOpposition $opposition): void
    {
        try {
            $email = (new Email())
                ->from($this->defaultFromEmail)
                ->to($opposition->getRequestedBy()->getEmail())
                ->subject('Opposition traitée - Ref: ' . $opposition->getReference())
                ->html($this->buildOppositionProcessedTemplate($opposition));

            $this->mailer->send($email);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email traitement opposition', [
                'opposition_id' => $opposition->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    private function buildSubscriptionCreatedTemplate(CardSubscription $subscription): string
    {
        return sprintf('
            <h2>Demande de carte enregistrée</h2>
            <p>Bonjour %s,</p>
            <p>Votre demande de carte %s %s a été enregistrée avec succès.</p>
            <p><strong>Référence :</strong> %s</p>
            <p>Elle sera examinée sous 2-3 jours ouvrables. Vous recevrez une notification dès qu\'elle sera traitée.</p>
            <p>Cordialement,<br>L\'équipe SEDEF BANK</p>
        ',
            $subscription->getUser()->getFirstname(),
            $subscription->getCardBrand(),
            $subscription->getCardType(),
            $subscription->getReference()
        );
    }

    private function buildSubscriptionApprovedTemplate(CardSubscription $subscription): string
    {
        return sprintf('
            <h2>Votre carte a été approuvée !</h2>
            <p>Bonjour %s,</p>
            <p>Excellente nouvelle ! Votre demande de carte %s %s a été approuvée.</p>
            <p><strong>Référence :</strong> %s</p>
            <p>Votre carte sera expédiée sous 5-7 jours ouvrables à votre adresse.</p>
            <p>Vous recevrez les informations d\'activation par courrier séparé.</p>
            <p>Cordialement,<br>L\'équipe SEDEF BANK</p>
        ',
            $subscription->getUser()->getFirstname(),
            $subscription->getCardBrand(),
            $subscription->getCardType(),
            $subscription->getReference()
        );
    }

    private function buildSubscriptionRejectedTemplate(CardSubscription $subscription): string
    {
        return sprintf('
            <h2>Votre demande de carte</h2>
            <p>Bonjour %s,</p>
            <p>Nous vous informons que votre demande de carte %s %s n\'a pas pu être approuvée.</p>
            <p><strong>Référence :</strong> %s</p>
            <p><strong>Motif :</strong> %s</p>
            <p>N\'hésitez pas à nous contacter pour plus d\'informations.</p>
            <p>Cordialement,<br>L\'équipe SEDEF BANK</p>
        ',
            $subscription->getUser()->getFirstname(),
            $subscription->getCardBrand(),
            $subscription->getCardType(),
            $subscription->getReference(),
            $subscription->getRejectionReason() ?? 'Non spécifié'
        );
    }

    private function buildOppositionCreatedTemplate(CardOpposition $opposition): string
    {
        return sprintf('
            <h2>Opposition enregistrée - Carte bloquée</h2>
            <p>Bonjour %s,</p>
            <p>Votre opposition a été enregistrée avec succès. Votre carte se terminant par %s a été immédiatement bloquée.</p>
            <p><strong>Référence :</strong> %s</p>
            <p><strong>Motif :</strong> %s</p>
            <p>%s</p>
            <p>En cas d\'urgence, contactez-nous au 02/XXX.XX.XX (24h/24).</p>
            <p>Cordialement,<br>L\'équipe SEDEF BANK</p>
        ',
            $opposition->getRequestedBy()->getFirstname(),
            substr($opposition->getCard()->getCardNumber(), -4),
            $opposition->getReference(),
            $this->translateCardReason($opposition->getReason()),
            $opposition->getNewCardRequested() ? 'Une nouvelle carte sera émise automatiquement.' : 'Aucune carte de remplacement demandée.'
        );
    }

    private function buildOppositionProcessedTemplate(CardOpposition $opposition): string
    {
        return sprintf('
            <h2>Opposition traitée</h2>
            <p>Bonjour %s,</p>
            <p>Votre opposition a été traitée par nos services.</p>
            <p><strong>Référence :</strong> %s</p>
            <p>%s</p>
            <p>Cordialement,<br>L\'équipe SEDEF BANK</p>
        ',
            $opposition->getRequestedBy()->getFirstname(),
            $opposition->getReference(),
            $opposition->getReplacementSubscription() ? 
                'Une nouvelle carte a été commandée automatiquement. Référence : ' . $opposition->getReplacementSubscription()->getReference() :
                'Aucune carte de remplacement n\'a été demandée.'
        );
    }

    private function translateCardReason(string $reason): string
    {
        return match($reason) {
            'lost' => 'Carte perdue',
            'stolen' => 'Carte volée',
            'compromised' => 'Carte compromise',
            'fraudulent_activity' => 'Activité frauduleuse',
            'damaged' => 'Carte endommagée',
            'other' => 'Autre motif',
            default => $reason
        };
    }
}
