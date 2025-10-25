<?php

namespace App\Service;

use App\Entity\CardOpposition;
use App\Entity\CardSubscription;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Service de notification spécialisé pour les cartes bancaires
 * Gère les emails liés aux souscriptions et oppositions
 */
class CardNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private ProfessionalTranslationService $translationService,
        private LoggerInterface $logger,
        private string $defaultFromEmail,
        private string $defaultFromName
    ) {
    }

    /**
     * Notification de création de souscription
     */
    public function notifySubscriptionCreated(CardSubscription $subscription): void
    {
        $this->sendEmailWithLocale($subscription->getUser(), function($user) use ($subscription) {
            $email = (new TemplatedEmail())
                ->from(new Address($this->defaultFromEmail, $this->defaultFromName))
                ->to($subscription->getUser()->getEmail())
                ->subject($this->translationService->tp('email_card_subscription_created.title', [], 'email_card_subscription_created'))
                ->htmlTemplate('email/card/subscription_created.html.twig')
                ->context([
                    'subscription' => $subscription,
                    'user' => $subscription->getUser(),
                ]);

            $this->mailer->send($email);
            
            $this->logger->info('Email de confirmation souscription envoyé', [
                'subscription_id' => $subscription->getId(),
                'user_email' => $subscription->getUser()->getEmail()
            ]);
        });
    }

    /**
     * Notification d'approbation de souscription
     */
    public function notifySubscriptionApproved(CardSubscription $subscription): void
    {
        $this->sendEmailWithLocale($subscription->getUser(), function($user) use ($subscription) {
            $email = (new TemplatedEmail())
                ->from(new Address($this->defaultFromEmail, $this->defaultFromName))
                ->to($subscription->getUser()->getEmail())
                ->subject($this->translationService->tp('email_card_subscription_approved.title', [], 'email_card_subscription_approved'))
                ->htmlTemplate('email/card/subscription_approved.html.twig')
                ->context([
                    'subscription' => $subscription,
                    'user' => $subscription->getUser(),
                ]);

            $this->mailer->send($email);
            
            $this->logger->info('Email d\'approbation souscription envoyé', [
                'subscription_id' => $subscription->getId(),
                'user_email' => $subscription->getUser()->getEmail()
            ]);
        });
    }

    /**
     * Notification de rejet de souscription
     */
    public function notifySubscriptionRejected(CardSubscription $subscription): void
    {
        $this->sendEmailWithLocale($subscription->getUser(), function($user) use ($subscription) {
            $email = (new TemplatedEmail())
                ->from(new Address($this->defaultFromEmail, $this->defaultFromName))
                ->to($subscription->getUser()->getEmail())
                ->subject($this->translationService->tp('email_card_subscription_rejected.title', [], 'email_card_subscription_rejected'))
                ->htmlTemplate('email/card/subscription_rejected.html.twig')
                ->context([
                    'subscription' => $subscription,
                    'user' => $subscription->getUser(),
                ]);

            $this->mailer->send($email);
            
            $this->logger->info('Email de rejet souscription envoyé', [
                'subscription_id' => $subscription->getId(),
                'user_email' => $subscription->getUser()->getEmail()
            ]);
        });
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
            $this->translateReason($opposition->getReason()),
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

    /**
     * Notification de signature de contrat
     */
    public function notifyContractSigned(CardSubscription $subscription): void
    {
        try {
            $this->sendEmailWithLocale($subscription->getUser(), function($user) use ($subscription) {
                $email = (new TemplatedEmail())
                    ->from(new Address($this->defaultFromEmail, $this->defaultFromName))
                    ->to($subscription->getUser()->getEmail())
                    ->subject($this->translationService->tp('email_card_contract_signed.title', [], 'email_card_contract_signed'))
                    ->htmlTemplate('email/card/contract_signed.html.twig')
                    ->context([
                        'subscription' => $subscription,
                        'user' => $subscription->getUser(),
                    ]);

                $this->mailer->send($email);
            });

            // Email à l'admin pour notification
            $adminEmailMessage = (new Email())
                ->from($this->defaultFromEmail)
                ->to($this->defaultFromEmail)
                ->subject('Contrat signé - Action requise')
                ->html("
                    <h2>Nouveau contrat signé</h2>
                    <p>Le contrat de souscription {$subscription->getReference()} a été signé.</p>
                    <p><strong>Client :</strong> {$subscription->getUser()->getFirstName()} {$subscription->getUser()->getLastName()}</p>
                    <p><strong>Email :</strong> {$subscription->getUser()->getEmail()}</p>
                    <p><strong>Type de carte :</strong> {$subscription->getCardType()}</p>
                    <p>Action requise : Valider le paiement et créer la carte.</p>
                ");

            $this->mailer->send($adminEmailMessage);

            $this->logger->info('Notifications de signature de contrat envoyées', [
                'subscription_reference' => $subscription->getReference(),
                'user_email' => $subscription->getUser()->getEmail()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi des notifications de signature', [
                'subscription_reference' => $subscription->getReference(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notification de carte prête
     */
    public function notifyCardReady(CardSubscription $subscription, $card): void
    {
        try {
            $this->sendEmailWithLocale($subscription->getUser(), function($user) use ($subscription, $card) {
                $email = (new TemplatedEmail())
                    ->from(new Address($this->defaultFromEmail, $this->defaultFromName))
                    ->to($subscription->getUser()->getEmail())
                    ->subject($this->translationService->tp('email_card_ready.title', [], 'email_card_ready'))
                    ->htmlTemplate('email/card/card_ready.html.twig')
                    ->context([
                        'subscription' => $subscription,
                        'user' => $subscription->getUser(),
                        'card' => $card,
                    ]);

                $this->mailer->send($email);
            });

            $this->logger->info('Notification de carte prête envoyée', [
                'subscription_reference' => $subscription->getReference(),
                'card_number' => substr($card->getCardNumber(), -4),
                'user_email' => $subscription->getUser()->getEmail()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de la notification de carte prête', [
                'subscription_reference' => $subscription->getReference(),
                'error' => $e->getMessage()
            ]);
        }
    }

    private function translateReason(string $reason): string
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

    /**
     * Envoie un email en gérant la locale de l'utilisateur
     */
    private function sendEmailWithLocale(User $user, callable $emailCallback): void
    {
        try {
            // Gérer la locale de l'utilisateur
            $userLocale = $user->getLanguage() ?? 'fr';
            $supportedLocales = ['fr', 'nl', 'de', 'en', 'es'];
            if (!in_array($userLocale, $supportedLocales, true)) {
                $userLocale = 'fr';
            }
            
            // Sauvegarder la locale actuelle
            $originalLocale = $this->translationService->getLocale();
            $this->translationService->setLocale($userLocale);
            
            // Exécuter le callback pour créer et envoyer l'email
            $emailCallback($user);
            
            // Restaurer la locale originale
            $this->translationService->setLocale($originalLocale);
            
        } catch (\Exception $e) {
            // Restaurer la locale en cas d'erreur
            if (isset($originalLocale)) {
                $this->translationService->setLocale($originalLocale);
            }
            
            $this->logger->error('Erreur lors de l\'envoi d\'email avec locale', [
                'user_email' => $user->getEmail(),
                'user_locale' => $userLocale ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
