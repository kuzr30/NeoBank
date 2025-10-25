<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

final class LocalizedRoutingService
{
    private array $routeTranslations = [
        'fr' => [
            'home' => '',
            'home_index' => '',
            'login' => 'connexion',
            'logout' => 'deconnexion',
            'app_login' => 'connexion',
            'app_logout' => 'deconnexion',
            'register' => 'inscription',
            'app_register' => 'inscription',
            'credit_simulation' => 'simulation-credit',
            'credit_simulation_index' => 'simulation-credit',
            'credit_simulation_calculate' => 'simulation-credit/calculer',
            'credit_simulation_result' => 'simulation-credit/resultat',
            'credit_simulation_amortization_table' => 'simulation-credit/tableau-amortissement',
            'credit_application' => 'demande-credit',
            'credit_application_start' => 'ma-demande-de-credit',
            'credit_application_step' => 'ma-demande-de-credit/etape',
            'credit_application_summary' => 'ma-demande-de-credit/recapitulatif',
            'credit_application_submit' => 'ma-demande-de-credit/soumettre',
            'credit_application_confirmation' => 'ma-demande-de-credit/confirmation',
            // Anciennes routes spécifiques (gardées pour compatibilité)
            'credit_application_step1' => 'demande-credit/etape-1',
            'credit_application_step2' => 'demande-credit/etape-2',
            'credit_application_step3' => 'demande-credit/etape-3',
            'credit_application_step4' => 'demande-credit/etape-4',
            'credit_application_credit_details' => 'demande-credit/details-credit',
            'about' => 'a-propos',
            'contact' => 'contact',
            'legal' => 'mentions-legales',
            'privacy' => 'confidentialite',
            // Routes d'activation de compte (AccountActivationController)
            'app_account_activation_notice' => 'notification-activation',
            'app_account_activation' => 'activer-compte',
            'app_resend_activation' => 'renvoyer-activation',
            // Routes de crédit (OurCreditOfferController)
            'credit_offers_index' => 'offres-credit',
            'credit_offers_personal_loan' => 'offres-credit/credit-personnel',
            'credit_offers_travel_loan' => 'offres-credit/credit-voyage',
            'credit_offers_auto_loan' => 'offres-credit/credit-auto',
            'credit_offers_home_loan' => 'offres-credit/credit-immobilier',
            'credit_offers_hypothecaire' => 'offres-credit/hypothecaire',
            'credit_offers_travaux' => 'offres-credit/travaux',
            'credit_offers_amelioration_habitat' => 'offres-credit/amelioration-habitat',
            'credit_offers_credit_relais' => 'offres-credit/credit-relais',
            'credit_offers_leasing_automobile' => 'offres-credit/leasing-automobile',
            'credit_offers_credit_consommation' => 'offres-credit/credit-consommation',
            'credit_offers_regroupement_credits' => 'offres-credit/regroupement-credits',
            'credit_offers_credit_renouvelable' => 'offres-credit/credit-renouvelable',
            'credit_offers_credit_professionnel' => 'offres-credit/credit-professionnel',
            'credit_offers_credit_etudiant' => 'offres-credit/credit-etudiant',
            'credit_offers_microcredit' => 'offres-credit/microcredit',
            // Routes de services (ServicesController)
            'services_accounts_cards' => 'services/comptes-cartes',
            'services_savings_investments' => 'services/epargne-placements',
            'services_insurances' => 'services/assurances',
            'services_devis_form' => 'services/assurances/devis',
            'services_devis_confirmation' => 'services/assurances/devis/confirmation',
            // Routes de services bancaires (BankingServicesController)
            'banking_mobile_app' => 'mobile-banking/application-mobile',
            'banking_credit_card' => 'mobile-banking/carte-credit',
            
            // Routes banking principales (BankingController)
            'banking_dashboard' => 'banking/dashboard',
            'banking_comptes' => 'banking/comptes',
            'banking_cartes' => 'banking/cartes',
            'banking_virements' => 'banking/virements',
            'banking_epargne' => 'banking/epargne',
            'banking_credits' => 'banking/credits',
            'banking_assurances' => 'banking/assurances',
            'banking_ribs' => 'banking/mes-beneficiaires',
            
            // Routes banking - Comptes (AccountController)
            'banking_accounts' => 'banking/comptes',
            'banking_account_detail' => 'banking/comptes',
            
            // Routes banking - Cartes (CardController)  
            'banking_cards' => 'banking/cartes',
            'app_banking_card_index' => 'banking/cartes',
            'app_banking_card_subscription_classic' => 'banking/cartes/souscrire/classic',
            'app_banking_card_subscription_gold' => 'banking/cartes/souscrire/gold',
            'app_banking_card_subscription_platinum' => 'banking/cartes/souscrire/platinum',
            'app_banking_card_subscription_show' => 'banking/cartes/souscrire',
            'app_banking_card_subscription_cancel' => 'banking/cartes/souscrire/annuler',
            'app_banking_card_subscription_status' => 'banking/cartes/souscrire/statut',
            
            // Routes banking - Opposition de cartes (OppositionController)
            'app_card_opposition_index' => 'banking/cartes/opposition',
            'app_card_opposition_create' => 'banking/cartes/opposition/carte',
            'app_card_opposition_show' => 'banking/cartes/opposition/opposition',
            'app_card_opposition_emergency' => 'banking/cartes/opposition/urgence',
            'app_card_opposition_status' => 'banking/cartes/opposition/opposition/statut',
            
            // Routes banking - Crédits (CreditController)
            'banking_credits' => 'banking/credits',
            'banking_credits_detailed' => 'banking/credits',
            'banking_credit_detail' => 'banking/credits',
            'banking_credit_application_detail' => 'banking/credits/demande',
            'banking_credit_amortization' => 'banking/credits',
            'banking_credit_simulation' => 'banking/credits/simulation/nouveau',
            'banking_credit_application' => 'banking/credits/demande/nouvelle',
            
            // Routes banking - Virements de crédit (CreditTransferController)
            'banking_credit_transfer_form' => 'banking/virement-credit/form',
            'banking_credit_transfer_initiate' => 'banking/virement-credit/initiate',
            'banking_credit_transfer_confirm' => 'banking/virement-credit/confirm',
            
            // Routes banking - Prêts (LoanController)
            'banking_loans' => 'banking/prets',
            'banking_loan_detail' => 'banking/prets',
            
            // Routes banking - Bénéficiaires (RibController)
            'banking_ribs_index' => 'banking/mes-beneficiaires',
            'banking_rib_new' => 'banking/mes-beneficiaires/nouveau',
            'banking_rib_show' => 'banking/mes-beneficiaires',
            'banking_rib_delete' => 'banking/mes-beneficiaires',
            
            // Routes banking - Transactions (TransactionController)
            'banking_transactions' => 'banking/transactions',
            
            // Routes banking - Virements (TransferController)
            'banking_transfers' => 'banking/virements',
            'banking_transfer_new' => 'banking/virements/nouveau',
            'banking_transfer_validate' => 'banking/virements/{id}/valider',
            'banking_transfer_details' => 'banking/virements/{id}/details',
            'banking_transfer_cancel' => 'banking/virements/{id}/annuler',
            
            // Routes de contrats (ContractController)
            'contract_download' => 'contrats/telecharger',
            'contract_view' => 'contrats/voir',
            
            // Routes de contrats de souscription (ContractSubscriptionController)
            'contract_signature' => 'contrats/signature',
            'contract_sign_process' => 'contrats/signature/signer',
            'contract_signature_success' => 'contrats/signature/succes',
            'card_contract_download' => 'contrats/carte/telecharger',
            
            // Routes de profil (ProfileController)
            'profile_index' => 'profil',
            'profile_edit' => 'profil/modifier',
            'profile_security' => 'profil/securite',
            'profile_security_change_password' => 'profil/securite/changer-mot-de-passe',
            'profile_preferences' => 'profil/preferences',
            
            // Routes de réinitialisation de mot de passe
            'app_forgot_password_request' => 'reinitialiser-mot-de-passe',
            'app_check_email' => 'reinitialiser-mot-de-passe/verifier-email',
            'app_reset_password' => 'reinitialiser-mot-de-passe/reinitialiser',
            
            // Routes KYC (KycController)
            'kyc_index' => 'profil/kyc',
            'kyc_submit' => 'profil/kyc/soumettre',
            'kyc_status' => 'profil/kyc/statut',
            
            // Routes d'erreur (Error pages)
            'error_404' => 'erreur/page-non-trouvee',
            'error_403' => 'erreur/acces-interdit',
            'error_500' => 'erreur/erreur-serveur',
            'error_generic' => 'erreur',
            
            // Routes support (SupportController)
            'support_help_center' => 'aide/centre-aide',
            'support_contact' => 'contact',
            'support_branches' => 'agences',
            'support_faq' => 'faq',
            'support_security' => 'securite',
            'support_complaints' => 'reclamations',
            
            // Routes légales (LegalController)
            'legal_notices' => 'mentions-legales',
            'legal_terms' => 'conditions-generales',
            'legal_privacy' => 'confidentialite',
            'legal_cookies' => 'cookies',
        ],
        'nl' => [
            'home' => '',
            'home_index' => '',
            'login' => 'inloggen',
            'logout' => 'uitloggen',
            'app_login' => 'inloggen',
            'app_logout' => 'uitloggen',
            'register' => 'registreren',
            'app_register' => 'registreren',
            'credit_simulation' => 'krediet-simulatie',
            'credit_simulation_index' => 'krediet-simulatie',
            'credit_simulation_calculate' => 'krediet-simulatie/berekenen',
            'credit_simulation_result' => 'krediet-simulatie/resultaat',
            'credit_simulation_amortization_table' => 'krediet-simulatie/aflossingstabel',
            'credit_application' => 'krediet-aanvraag',
            'credit_application_start' => 'mijn-kredietaanvraag',
            'credit_application_step' => 'mijn-kredietaanvraag/stap',
            'credit_application_summary' => 'mijn-kredietaanvraag/samenvatting',
            'credit_application_submit' => 'mijn-kredietaanvraag/indienen',
            'credit_application_confirmation' => 'mijn-kredietaanvraag/bevestiging',
            // Anciennes routes spécifiques (gardées pour compatibilité)
            'credit_application_step1' => 'krediet-aanvraag/stap-1',
            'credit_application_step2' => 'krediet-aanvraag/stap-2',
            'credit_application_step3' => 'krediet-aanvraag/stap-3',
            'credit_application_step4' => 'krediet-aanvraag/stap-4',
            'credit_application_credit_details' => 'krediet-aanvraag/krediet-details',
            'about' => 'over-ons',
            'contact' => 'contact',
            'legal' => 'juridische-informatie',
            'privacy' => 'privacy',
            // Routes d'activation de compte (AccountActivationController)
            'app_account_activation_notice' => 'activatie-melding',
            'app_account_activation' => 'account-activeren',
            'app_resend_activation' => 'activatie-opnieuw-versturen',
            // Routes de crédit (OurCreditOfferController)
            'credit_offers_index' => 'krediet-aanbod',
            'credit_offers_personal_loan' => 'krediet-aanbod/persoonlijke-lening',
            'credit_offers_travel_loan' => 'krediet-aanbod/reisgeld',
            'credit_offers_auto_loan' => 'krediet-aanbod/autolening',
            'credit_offers_home_loan' => 'krediet-aanbod/hypotheek',
            'credit_offers_hypothecaire' => 'krediet-aanbod/hypothecair',
            'credit_offers_travaux' => 'krediet-aanbod/verbouwing',
            'credit_offers_amelioration_habitat' => 'krediet-aanbod/woning-verbetering',
            'credit_offers_credit_relais' => 'krediet-aanbod/overbruggingskredit',
            'credit_offers_leasing_automobile' => 'krediet-aanbod/auto-lease',
            'credit_offers_credit_consommation' => 'krediet-aanbod/consumptie-krediet',
            'credit_offers_regroupement_credits' => 'krediet-aanbod/krediet-hergroepering',
            'credit_offers_credit_renouvelable' => 'krediet-aanbod/hernieuwbaar-krediet',
            'credit_offers_credit_professionnel' => 'krediet-aanbod/zakelijk-krediet',
            'credit_offers_credit_etudiant' => 'krediet-aanbod/studenten-lening',
            'credit_offers_microcredit' => 'krediet-aanbod/microkrediet',
            // Routes de services (ServicesController)
            'services_accounts_cards' => 'services/rekeningen-kaarten',
            'services_savings_investments' => 'services/sparen-beleggingen',
            'services_insurances' => 'services/verzekeringen',
            'services_devis_form' => 'services/verzekeringen/offerte',
            'services_devis_confirmation' => 'services/verzekeringen/offerte/bevestiging',
            // Routes de services bancaires (BankingServicesController)
            'banking_mobile_app' => 'mobiel-bankieren/mobiele-app',
            'banking_credit_card' => 'mobiel-bankieren/kredietkaart',
            
            // Routes banking principales (BankingController)
            'banking_dashboard' => 'banking/dashboard',
            'banking_comptes' => 'banking/rekeningen',
            'banking_cartes' => 'banking/kaarten',
            'banking_virements' => 'banking/overboekingen',
            'banking_epargne' => 'banking/sparen',
            'banking_credits' => 'banking/kredieten',
            'banking_assurances' => 'banking/verzekeringen',
            'banking_ribs' => 'banking/mijn-begunstigden',
            
            // Routes banking - Rekeningen (AccountController)
            'banking_accounts' => 'banking/rekeningen',
            'banking_account_detail' => 'banking/rekeningen',
            
            // Routes banking - Kaarten (CardController)  
            'banking_cards' => 'banking/kaarten',
            'app_banking_card_index' => 'banking/kaarten',
            'app_banking_card_subscription_classic' => 'banking/kaarten/abonneren/classic',
            'app_banking_card_subscription_gold' => 'banking/kaarten/abonneren/gold',
            'app_banking_card_subscription_platinum' => 'banking/kaarten/abonneren/platinum',
            'app_banking_card_subscription_show' => 'banking/kaarten/abonneren',
            'app_banking_card_subscription_cancel' => 'banking/kaarten/abonneren/annuleren',
            'app_banking_card_subscription_status' => 'banking/kaarten/abonneren/status',
            
            // Routes banking - Kaarten blokkering (OppositionController)
            'app_card_opposition_index' => 'banking/kaarten/blokkering',
            'app_card_opposition_create' => 'banking/kaarten/blokkering/kaart',
            'app_card_opposition_show' => 'banking/kaarten/blokkering/blokkering',
            'app_card_opposition_emergency' => 'banking/kaarten/blokkering/noodgeval',
            'app_card_opposition_status' => 'banking/kaarten/blokkering/blokkering/status',
            
            // Routes banking - Kredieten (CreditController)
            'banking_credits' => 'banking/kredieten',
            'banking_credits_detailed' => 'banking/kredieten',
            'banking_credit_detail' => 'banking/kredieten',
            'banking_credit_application_detail' => 'banking/kredieten/aanvraag',
            'banking_credit_amortization' => 'banking/kredieten',
            'banking_credit_simulation' => 'banking/kredieten/simulatie/nieuw',
            'banking_credit_application' => 'banking/kredieten/aanvraag/nieuw',
            
            // Routes banking - Krediet overboekingen (CreditTransferController)
            'banking_credit_transfer_form' => 'banking/krediet-overboeking/form',
            'banking_credit_transfer_initiate' => 'banking/krediet-overboeking/initiate',
            'banking_credit_transfer_confirm' => 'banking/krediet-overboeking/confirm',
            
            // Routes banking - Leningen (LoanController)
            'banking_loans' => 'banking/leningen',
            'banking_loan_detail' => 'banking/leningen',
            
            // Routes banking - Begunstigden (RibController)
            'banking_ribs_index' => 'banking/mijn-begunstigden',
            'banking_rib_new' => 'banking/mijn-begunstigden/nieuw',
            'banking_rib_show' => 'banking/mijn-begunstigden',
            'banking_rib_delete' => 'banking/mijn-begunstigden',
            
            // Routes banking - Transacties (TransactionController)
            'banking_transactions' => 'banking/transacties',
            
            // Routes banking - Overboekingen (TransferController)
            'banking_transfers' => 'banking/overboekingen',
            'banking_transfer_new' => 'banking/overboekingen/nieuw',
            'banking_transfer_validate' => 'banking/overboekingen/{id}/valideren',
            'banking_transfer_details' => 'banking/overboekingen/{id}/details',
            'banking_transfer_cancel' => 'banking/overboekingen/{id}/annuleren',
            
            // Routes de contracten (ContractController)
            'contract_download' => 'contracten/downloaden',
            'contract_view' => 'contracten/bekijken',
            
            // Routes van contracten voor abonnementen (ContractSubscriptionController)
            'contract_signature' => 'contracten/handtekening',
            'contract_sign_process' => 'contracten/handtekening/ondertekenen',
            'contract_signature_success' => 'contracten/handtekening/succes',
            'card_contract_download' => 'contracten/kaart/downloaden',
            
            // Routes van profiel (ProfileController)
            'profile_index' => 'profiel',
            'profile_edit' => 'profiel/bewerken',
            'profile_security' => 'profiel/beveiliging',
            'profile_security_change_password' => 'profiel/beveiliging/wachtwoord-wijzigen',
            'profile_preferences' => 'profiel/voorkeuren',
            
            // Routes de réinitialisation de mot de passe
            'app_forgot_password_request' => 'wachtwoord-resetten',
            'app_check_email' => 'wachtwoord-resetten/controleer-email',
            'app_reset_password' => 'wachtwoord-resetten/resetten',
            
            // Routes KYC (KycController)
            'kyc_index' => 'profiel/kyc',
            'kyc_submit' => 'profiel/kyc/indienen',
            'kyc_status' => 'profiel/kyc/status',
            
            // Routes d'erreur (Error pages)
            'error_404' => 'fout/pagina-niet-gevonden',
            'error_403' => 'fout/toegang-geweigerd',
            'error_500' => 'fout/server-fout',
            'error_generic' => 'fout',
            
            // Routes support (SupportController)
            'support_help_center' => 'hulp/helpcentrum',
            'support_contact' => 'contact',
            'support_branches' => 'kantoren',
            'support_faq' => 'veelgestelde-vragen',
            'support_security' => 'beveiliging',
            'support_complaints' => 'klachten',
            
            // Routes légales (LegalController)
            'legal_notices' => 'juridische-vermeldingen',
            'legal_terms' => 'algemene-voorwaarden',
            'legal_privacy' => 'privacy-beleid',
            'legal_cookies' => 'cookies',
        ],
        'de' => [
            'home' => '',
            'home_index' => '',
            'login' => 'anmelden',
            'logout' => 'abmelden',
            'app_login' => 'anmelden',
            'app_logout' => 'abmelden',
            'register' => 'registrieren',
            'app_register' => 'registrieren',
            'credit_simulation' => 'kredit-simulation',
            'credit_simulation_index' => 'kredit-simulation',
            'credit_simulation_calculate' => 'kredit-simulation/berechnen',
            'credit_simulation_result' => 'kredit-simulation/ergebnis',
            'credit_simulation_amortization_table' => 'kredit-simulation/tilgungsplan',
            'credit_application' => 'kreditantrag',
            'credit_application_start' => 'mein-kreditantrag',
            'credit_application_step' => 'mein-kreditantrag/schritt',
            'credit_application_summary' => 'mein-kreditantrag/zusammenfassung',
            'credit_application_submit' => 'mein-kreditantrag/einreichen',
            'credit_application_confirmation' => 'mein-kreditantrag/bestaetigung',
            // Anciennes routes spécifiques (gardées pour compatibilité)
            'credit_application_step1' => 'kreditantrag/schritt-1',
            'credit_application_step2' => 'kreditantrag/schritt-2',
            'credit_application_step3' => 'kreditantrag/schritt-3',
            'credit_application_step4' => 'kreditantrag/schritt-4',
            'credit_application_credit_details' => 'kreditantrag/kredit-details',
            'about' => 'uber-uns',
            'contact' => 'kontakt',
            'legal' => 'rechtliche-hinweise',
            'privacy' => 'datenschutz',
            // Routes d'activation de compte (AccountActivationController)
            'app_account_activation_notice' => 'aktivierung-hinweis',
            'app_account_activation' => 'konto-aktivieren',
            'app_resend_activation' => 'aktivierung-erneut-senden',
            // Routes de crédit (OurCreditOfferController)
            'credit_offers_index' => 'kredit-angebote',
            'credit_offers_personal_loan' => 'kredit-angebote/persönlicher-kredit',
            'credit_offers_travel_loan' => 'kredit-angebote/reisekredit',
            'credit_offers_auto_loan' => 'kredit-angebote/autokredit',
            'credit_offers_home_loan' => 'kredit-angebote/immobilienkredit',
            'credit_offers_hypothecaire' => 'kredit-angebote/hypothek',
            'credit_offers_travaux' => 'kredit-angebote/renovierung',
            'credit_offers_amelioration_habitat' => 'kredit-angebote/wohnungsverbesserung',
            'credit_offers_credit_relais' => 'kredit-angebote/überbrückungskredit',
            'credit_offers_leasing_automobile' => 'kredit-angebote/auto-leasing',
            'credit_offers_credit_consommation' => 'kredit-angebote/verbraucherkredit',
            'credit_offers_regroupement_credits' => 'kredit-angebote/kreditumschuldung',
            'credit_offers_credit_renouvelable' => 'kredit-angebote/erneuerbarer-kredit',
            'credit_offers_credit_professionnel' => 'kredit-angebote/geschäftskredit',
            'credit_offers_credit_etudiant' => 'kredit-angebote/studentenkredit',
            'credit_offers_microcredit' => 'kredit-angebote/mikrokredit',
            // Routes de services (ServicesController)
            'services_accounts_cards' => 'services/konten-karten',
            'services_savings_investments' => 'services/sparen-investitionen',
            'services_insurances' => 'services/versicherungen',
            'services_devis_form' => 'services/versicherungen/angebot',
            'services_devis_confirmation' => 'services/versicherungen/angebot/bestaetigung',
            // Routes de services bancaires (BankingServicesController)
            'banking_mobile_app' => 'mobiles-banking/mobile-app',
            'banking_credit_card' => 'mobiles-banking/kreditkarte',
            
            // Routes banking principales (BankingController)
            'banking_dashboard' => 'banking/dashboard',
            'banking_comptes' => 'banking/konten',
            'banking_cartes' => 'banking/karten',
            'banking_virements' => 'banking/uberweisungen',
            'banking_epargne' => 'banking/sparen',
            'banking_credits' => 'banking/kredite',
            'banking_assurances' => 'banking/versicherungen',
            'banking_ribs' => 'banking/meine-begunstigten',
            
            // Routes banking - Konten (AccountController)
            'banking_accounts' => 'banking/konten',
            'banking_account_detail' => 'banking/konten',
            
            // Routes banking - Karten (CardController)  
            'banking_cards' => 'banking/karten',
            'app_banking_card_index' => 'banking/karten',
            'app_banking_card_subscription_classic' => 'banking/karten/abonnieren/classic',
            'app_banking_card_subscription_gold' => 'banking/karten/abonnieren/gold',
            'app_banking_card_subscription_platinum' => 'banking/karten/abonnieren/platinum',
            'app_banking_card_subscription_show' => 'banking/karten/abonnieren',
            'app_banking_card_subscription_cancel' => 'banking/karten/abonnieren/stornieren',
            'app_banking_card_subscription_status' => 'banking/karten/abonnieren/status',
            
            // Routes banking - Kartensperrung (OppositionController)
            'app_card_opposition_index' => 'banking/karten/sperrung',
            'app_card_opposition_create' => 'banking/karten/sperrung/karte',
            'app_card_opposition_show' => 'banking/karten/sperrung/sperrung',
            'app_card_opposition_emergency' => 'banking/karten/sperrung/notfall',
            'app_card_opposition_status' => 'banking/karten/sperrung/sperrung/status',
            
            // Routes banking - Kredite (CreditController)
            'banking_credits' => 'banking/kredite',
            'banking_credits_detailed' => 'banking/kredite',
            'banking_credit_detail' => 'banking/kredite',
            'banking_credit_application_detail' => 'banking/kredite/antrag',
            'banking_credit_amortization' => 'banking/kredite',
            'banking_credit_simulation' => 'banking/kredite/simulation/neu',
            'banking_credit_application' => 'banking/kredite/antrag/neu',
            
            // Routes banking - Kredit-Überweisungen (CreditTransferController)
            'banking_credit_transfer_form' => 'banking/kredit-uberweisung/form',
            'banking_credit_transfer_initiate' => 'banking/kredit-uberweisung/initiate',
            'banking_credit_transfer_confirm' => 'banking/kredit-uberweisung/confirm',
            
            // Routes banking - Darlehen (LoanController)
            'banking_loans' => 'banking/darlehen',
            'banking_loan_detail' => 'banking/darlehen',
            
            // Routes banking - Begünstigte (RibController)
            'banking_ribs_index' => 'banking/meine-begunstigten',
            'banking_rib_new' => 'banking/meine-begunstigten/neu',
            'banking_rib_show' => 'banking/meine-begunstigten',
            'banking_rib_delete' => 'banking/meine-begunstigten',
            
            // Routes banking - Transaktionen (TransactionController)
            'banking_transactions' => 'banking/transaktionen',
            
            // Routes banking - Überweisungen (TransferController)
            'banking_transfers' => 'banking/uberweisungen',
            'banking_transfer_new' => 'banking/uberweisungen/neu',
            'banking_transfer_validate' => 'banking/uberweisungen/{id}/validieren',
            'banking_transfer_details' => 'banking/uberweisungen/{id}/details',
            'banking_transfer_cancel' => 'banking/uberweisungen/{id}/stornieren',
            
            // Routes de contrats (ContractController)
            'contract_download' => 'vertrage/herunterladen',
            'contract_view' => 'vertrage/ansehen',
            
            // Routes von Vertragsabonnements (ContractSubscriptionController)
            'contract_signature' => 'vertrage/unterschrift',
            'contract_sign_process' => 'vertrage/unterschrift/unterschreiben',
            'contract_signature_success' => 'vertrage/unterschrift/erfolg',
            'card_contract_download' => 'vertrage/karte/herunterladen',
            'contract_view' => 'vertrage/ansehen',
            
            // Routes profil
            'profile_index' => 'profil',
            'profile_edit' => 'profil/bearbeiten',
            'profile_security' => 'profil/sicherheit',
            'profile_security_change_password' => 'profil/sicherheit/passwort-andern',
            'profile_preferences' => 'profil/einstellungen',
            
            // Routes de réinitialisation de mot de passe
            'app_forgot_password_request' => 'passwort-zurucksetzen',
            'app_check_email' => 'passwort-zurucksetzen/email-prufen',
            'app_reset_password' => 'passwort-zurucksetzen/zurucksetzen',
            
            // Routes KYC
            'kyc_index' => 'profil/kyc',
            'kyc_submit' => 'profil/kyc/einreichen',
            'kyc_status' => 'profil/kyc/status',
            
            // Routes d'erreur (Error pages)
            'error_404' => 'fehler/seite-nicht-gefunden',
            'error_403' => 'fehler/zugriff-verweigert',
            'error_500' => 'fehler/server-fehler',
            'error_generic' => 'fehler',
            
            // Routes support (SupportController)
            'support_help_center' => 'hilfe/hilfezentrum',
            'support_contact' => 'kontakt',
            'support_branches' => 'filialen',
            'support_faq' => 'haeufige-fragen',
            'support_security' => 'sicherheit',
            'support_complaints' => 'beschwerden',
            
            // Routes légales (LegalController)
            'legal_notices' => 'rechtliche-hinweise',
            'legal_terms' => 'allgemeine-geschaeftsbedingungen',
            'legal_privacy' => 'datenschutz',
            'legal_cookies' => 'cookies',
        ],
        'en' => [
            'home' => '',
            'login' => 'login',
            'logout' => 'logout',
            'app_login' => 'login',
            'app_logout' => 'logout',
            'register' => 'register',
            'app_register' => 'register',
            // Routes de réinitialisation de mot de passe (ResetPasswordController)
            'app_forgot_password_request' => 'reset-password',
            'app_check_email' => 'reset-password/check-email',
            'app_reset_password' => 'reset-password/reset',
            'credit_simulation' => 'credit-simulation',
            'credit_simulation_index' => 'credit-simulation',
            'credit_simulation_calculate' => 'credit-simulation/calculate',
            'credit_simulation_result' => 'credit-simulation/result',
            'credit_simulation_amortization_table' => 'credit-simulation/amortization-table',
            'credit_application' => 'credit-application',
            'credit_application_start' => 'my-credit-application',
            'credit_application_step' => 'my-credit-application/step',
            'credit_application_summary' => 'my-credit-application/summary',
            'credit_application_submit' => 'my-credit-application/submit',
            'credit_application_confirmation' => 'my-credit-application/confirmation',
            'about' => 'about-us',
            'contact' => 'contact',
            'legal' => 'legal-information',
            'privacy' => 'privacy',
            // Routes d'activation de compte (AccountActivationController)
            'app_account_activation_notice' => 'activation-notice',
            'app_account_activation' => 'activate-account',
            'app_resend_activation' => 'resend-activation',
            // Routes de crédit (OurCreditOfferController)
            'credit_offers_index' => 'credit-offers',
            'credit_offers_personal_loan' => 'credit-offers/personal-loan',
            'credit_offers_travel_loan' => 'credit-offers/travel-loan',
            'credit_offers_auto_loan' => 'credit-offers/auto-loan',
            'credit_offers_home_loan' => 'credit-offers/home-loan',
            'credit_offers_hypothecaire' => 'credit-offers/hypothecary',
            'credit_offers_travaux' => 'credit-offers/renovation',
            'credit_offers_amelioration_habitat' => 'credit-offers/home-improvement',
            'credit_offers_credit_relais' => 'credit-offers/bridge-loan',
            'credit_offers_leasing_automobile' => 'credit-offers/car-leasing',
            'credit_offers_credit_consommation' => 'credit-offers/consumer-credit',
            'credit_offers_regroupement_credits' => 'credit-offers/debt-consolidation',
            'credit_offers_credit_renouvelable' => 'credit-offers/revolving-credit',
            'credit_offers_credit_professionnel' => 'credit-offers/business-loan',
            'credit_offers_credit_etudiant' => 'credit-offers/student-loan',
            'credit_offers_microcredit' => 'credit-offers/microcredit',
            // Routes de services (ServicesController)
            'services_accounts_cards' => 'services/accounts-cards',
            'services_savings_investments' => 'services/savings-investments',
            'services_insurances' => 'services/insurances',
            'services_devis_form' => 'services/insurances/quote',
            'services_devis_confirmation' => 'services/insurances/quote/confirmation',
            // Routes de services bancaires (BankingServicesController)
            'banking_mobile_app' => 'mobile-banking/mobile-app',
            'banking_credit_card' => 'mobile-banking/credit-card',
            // Routes banking principales (BankingController)
            'banking_dashboard' => 'banking/dashboard',
            'banking_comptes' => 'banking/accounts',
            'banking_cartes' => 'banking/cards',
            'banking_virements' => 'banking/transfers',
            'banking_epargne' => 'banking/savings',
            'banking_credits' => 'banking/credits',
            'banking_assurances' => 'banking/insurances',
            'banking_ribs' => 'banking/my-beneficiaries',
            
            // Routes banking - Accounts (AccountController)
            'banking_accounts' => 'banking/accounts',
            'banking_account_detail' => 'banking/accounts',
            
            // Routes banking - Cards (CardController)  
            'banking_cards' => 'banking/cards',
            'app_banking_card_index' => 'banking/cards',
            'app_banking_card_subscription_classic' => 'banking/cards/subscribe/classic',
            'app_banking_card_subscription_gold' => 'banking/cards/subscribe/gold',
            'app_banking_card_subscription_platinum' => 'banking/cards/subscribe/platinum',
            'app_banking_card_subscription_show' => 'banking/cards/subscribe',
            'app_banking_card_subscription_cancel' => 'banking/cards/subscribe/cancel',
            'app_banking_card_subscription_status' => 'banking/cards/subscribe/status',
            
            // Routes banking - Card Opposition (OppositionController)
            'app_card_opposition_index' => 'banking/cards/opposition',
            'app_card_opposition_create' => 'banking/cards/opposition/card',
            'app_card_opposition_show' => 'banking/cards/opposition/opposition',
            'app_card_opposition_emergency' => 'banking/cards/opposition/emergency',
            'app_card_opposition_status' => 'banking/cards/opposition/opposition/status',
            
            // Routes banking - Credits (CreditController)
            'banking_credits' => 'banking/credits',
            'banking_credits_detailed' => 'banking/credits',
            'banking_credit_detail' => 'banking/credits',
            'banking_credit_application_detail' => 'banking/credits/application',
            'banking_credit_amortization' => 'banking/credits',
            'banking_credit_simulation' => 'banking/credits/simulation/new',
            'banking_credit_application' => 'banking/credits/application/new',
            
            // Routes banking - Credit Transfers (CreditTransferController)
            'banking_credit_transfer_form' => 'banking/credit-transfer/form',
            'banking_credit_transfer_initiate' => 'banking/credit-transfer/initiate',
            'banking_credit_transfer_confirm' => 'banking/credit-transfer/confirm',
            
            // Routes banking - Loans (LoanController)
            'banking_loans' => 'banking/loans',
            'banking_loan_detail' => 'banking/loans',
            
            // Routes banking - Beneficiaries (RibController)
            'banking_ribs_index' => 'banking/my-beneficiaries',
            'banking_rib_new' => 'banking/my-beneficiaries/new',
            'banking_rib_show' => 'banking/my-beneficiaries',
            'banking_rib_delete' => 'banking/my-beneficiaries',
            
            // Routes banking - Transactions (TransactionController)
            'banking_transactions' => 'banking/transactions',
            
            // Routes banking - Transfers (TransferController)
            'banking_transfers' => 'banking/transfers',
            'banking_transfer_new' => 'banking/transfers/new',
            'banking_transfer_validate' => 'banking/transfers/{id}/validate',
            'banking_transfer_details' => 'banking/transfers/{id}/details',
            'banking_transfer_cancel' => 'banking/transfers/{id}/cancel',
            
            // Routes contracts (ContractController)
            'contract_download' => 'contracts/download',
            'contract_view' => 'contracts/view',
            
            // Routes contract subscriptions (ContractSubscriptionController)
            'contract_signature' => 'contracts/signature',
            'contract_sign_process' => 'contracts/signature/sign',
            'contract_signature_success' => 'contracts/signature/success',
            'card_contract_download' => 'contracts/card/download',
            
            // Routes profile
            'profile_index' => 'profile',
            'profile_edit' => 'profile/edit',
            'profile_security' => 'profile/security',
            'profile_security_change_password' => 'profile/security/change-password',
            'profile_preferences' => 'profile/preferences',
            
            // Routes KYC
            'kyc_index' => 'profile/kyc',
            'kyc_submit' => 'profile/kyc/submit',
            'kyc_status' => 'profile/kyc/status',
            
            // Routes d'erreur (Error pages)
            'error_404' => 'error/page-not-found',
            'error_403' => 'error/access-denied',
            'error_500' => 'error/server-error',
            'error_generic' => 'error',
            
            // Routes support (SupportController)
            'support_help_center' => 'help/help-center',
            'support_contact' => 'contact',
            'support_branches' => 'branches',
            'support_faq' => 'faq',
            'support_security' => 'security',
            'support_complaints' => 'complaints',
            
            // Routes légales (LegalController)
            'legal_notices' => 'legal-notices',
            'legal_terms' => 'terms-conditions',
            'legal_privacy' => 'privacy-policy',
            'legal_cookies' => 'cookies',
        ],
        'es' => [
            'home' => '',
            'login' => 'iniciar-sesion',
            'logout' => 'cerrar-sesion',
            'app_login' => 'iniciar-sesion',
            'app_logout' => 'cerrar-sesion',
            'register' => 'registro',
            'app_register' => 'registro',
            // Routes de réinitialisation de mot de passe (ResetPasswordController)
            'app_forgot_password_request' => 'restablecer-contraseña',
            'app_check_email' => 'restablecer-contraseña/verificar-email',
            'app_reset_password' => 'restablecer-contraseña/restablecer',
            'credit_simulation' => 'simulacion-credito',
            'credit_simulation_index' => 'simulacion-credito',
            'credit_simulation_calculate' => 'simulacion-credito/calcular',
            'credit_simulation_result' => 'simulacion-credito/resultado',
            'credit_simulation_amortization_table' => 'simulacion-credito/tabla-amortizacion',
            'credit_application' => 'solicitud-credito',
            'credit_application_start' => 'mi-solicitud-credito',
            'credit_application_step' => 'mi-solicitud-credito/paso',
            'credit_application_summary' => 'mi-solicitud-credito/resumen',
            'credit_application_submit' => 'mi-solicitud-credito/enviar',
            'credit_application_confirmation' => 'mi-solicitud-credito/confirmacion',
            'about' => 'acerca-de',
            'contact' => 'contacto',
            'legal' => 'informacion-legal',
            'privacy' => 'privacidad',
            // Routes d'activation de compte (AccountActivationController)
            'app_account_activation_notice' => 'notificacion-activacion',
            'app_account_activation' => 'activar-cuenta',
            'app_resend_activation' => 'reenviar-activacion',
            // Routes de crédit (OurCreditOfferController)
            'credit_offers_index' => 'ofertas-credito',
            'credit_offers_personal_loan' => 'ofertas-credito/prestamo-personal',
            'credit_offers_travel_loan' => 'ofertas-credito/prestamo-viaje',
            'credit_offers_auto_loan' => 'ofertas-credito/prestamo-auto',
            'credit_offers_home_loan' => 'ofertas-credito/prestamo-vivienda',
            'credit_offers_hypothecaire' => 'ofertas-credito/hipotecario',
            'credit_offers_travaux' => 'ofertas-credito/reformas',
            'credit_offers_amelioration_habitat' => 'ofertas-credito/mejoras-hogar',
            'credit_offers_credit_relais' => 'ofertas-credito/credito-puente',
            'credit_offers_leasing_automobile' => 'ofertas-credito/leasing-automovil',
            'credit_offers_credit_consommation' => 'ofertas-credito/credito-consumo',
            'credit_offers_regroupement_credits' => 'ofertas-credito/reunificacion-creditos',
            'credit_offers_credit_renouvelable' => 'ofertas-credito/credito-renovable',
            'credit_offers_credit_professionnel' => 'ofertas-credito/credito-profesional',
            'credit_offers_credit_etudiant' => 'ofertas-credito/credito-estudiante',
            'credit_offers_microcredit' => 'ofertas-credito/microcredito',
            // Routes de services (ServicesController)
            'services_accounts_cards' => 'servicios/cuentas-tarjetas',
            'services_savings_investments' => 'servicios/ahorros-inversiones',
            'services_insurances' => 'servicios/seguros',
            'services_devis_form' => 'servicios/seguros/cotizacion',
            'services_devis_confirmation' => 'servicios/seguros/cotizacion/confirmacion',
            // Routes de services bancaires (BankingServicesController)
            'banking_mobile_app' => 'banca-movil/aplicacion-movil',
            'banking_credit_card' => 'banca-movil/tarjeta-credito',
            // Routes banking principales (BankingController)
            'banking_dashboard' => 'banking/dashboard',
            'banking_comptes' => 'banking/cuentas',
            'banking_cartes' => 'banking/tarjetas',
            'banking_virements' => 'banking/transferencias',
            'banking_epargne' => 'banking/ahorros',
            'banking_credits' => 'banking/creditos',
            'banking_assurances' => 'banking/seguros',
            'banking_ribs' => 'banking/mis-beneficiarios',
            
            // Routes banking - Cuentas (AccountController)
            'banking_accounts' => 'banking/cuentas',
            'banking_account_detail' => 'banking/cuentas',
            
            // Routes banking - Tarjetas (CardController)  
            'banking_cards' => 'banking/tarjetas',
            'app_banking_card_index' => 'banking/tarjetas',
            'app_banking_card_subscription_classic' => 'banking/tarjetas/suscribir/classic',
            'app_banking_card_subscription_gold' => 'banking/tarjetas/suscribir/gold',
            'app_banking_card_subscription_platinum' => 'banking/tarjetas/suscribir/platinum',
            'app_banking_card_subscription_show' => 'banking/tarjetas/suscribir',
            'app_banking_card_subscription_cancel' => 'banking/tarjetas/suscribir/cancelar',
            'app_banking_card_subscription_status' => 'banking/tarjetas/suscribir/estado',
            
            // Routes banking - Oposición de tarjetas (OppositionController)
            'app_card_opposition_index' => 'banking/tarjetas/oposicion',
            'app_card_opposition_create' => 'banking/tarjetas/oposicion/tarjeta',
            'app_card_opposition_show' => 'banking/tarjetas/oposicion/oposicion',
            'app_card_opposition_emergency' => 'banking/tarjetas/oposicion/emergencia',
            'app_card_opposition_status' => 'banking/tarjetas/oposicion/oposicion/estado',
            
            // Routes banking - Créditos (CreditController)
            'banking_credits' => 'banking/creditos',
            'banking_credits_detailed' => 'banking/creditos',
            'banking_credit_detail' => 'banking/creditos',
            'banking_credit_application_detail' => 'banking/creditos/solicitud',
            'banking_credit_amortization' => 'banking/creditos',
            'banking_credit_simulation' => 'banking/creditos/simulacion/nuevo',
            'banking_credit_application' => 'banking/creditos/solicitud/nueva',
            
            // Routes banking - Transferencias de crédito (CreditTransferController)
            'banking_credit_transfer_form' => 'banking/transferencia-credito/form',
            'banking_credit_transfer_initiate' => 'banking/transferencia-credito/initiate',
            'banking_credit_transfer_confirm' => 'banking/transferencia-credito/confirm',
            
            // Routes banking - Préstamos (LoanController)
            'banking_loans' => 'banking/prestamos',
            'banking_loan_detail' => 'banking/prestamos',
            
            // Routes banking - Beneficiarios (RibController)
            'banking_ribs_index' => 'banking/mis-beneficiarios',
            'banking_rib_new' => 'banking/mis-beneficiarios/nuevo',
            'banking_rib_show' => 'banking/mis-beneficiarios',
            'banking_rib_delete' => 'banking/mis-beneficiarios',
            
            // Routes banking - Transacciones (TransactionController)
            'banking_transactions' => 'banking/transacciones',
            
            // Routes banking - Transferencias (TransferController)
            'banking_transfers' => 'banking/transferencias',
            'banking_transfer_new' => 'banking/transferencias/nuevo',
            'banking_transfer_validate' => 'banking/transferencias/{id}/validar',
            'banking_transfer_details' => 'banking/transferencias/{id}/detalles',
            'banking_transfer_cancel' => 'banking/transferencias/{id}/cancelar',
            
            // Routes contratos (ContractController)
            'contract_download' => 'contratos/descargar',
            'contract_view' => 'contratos/ver',
            
            // Routes suscripciones de contrato (ContractSubscriptionController)
            'contract_signature' => 'contratos/firma',
            'contract_sign_process' => 'contratos/firma/firmar',
            'contract_signature_success' => 'contratos/firma/exito',
            'card_contract_download' => 'contratos/tarjeta/descargar',
            
            // Routes perfil
            'profile_index' => 'perfil',
            'profile_edit' => 'perfil/editar',
            'profile_security' => 'perfil/seguridad',
            'profile_security_change_password' => 'perfil/seguridad/cambiar-contraseña',
            'profile_preferences' => 'perfil/preferencias',
            
            // Routes KYC
            'kyc_index' => 'perfil/kyc',
            'kyc_submit' => 'perfil/kyc/enviar',
            'kyc_status' => 'perfil/kyc/estado',
            
            // Routes d'erreur (Error pages)
            'error_404' => 'error/pagina-no-encontrada',
            'error_403' => 'error/acceso-denegado',
            'error_500' => 'error/error-servidor',
            'error_generic' => 'error',
            
            // Routes support (SupportController)
            'support_help_center' => 'ayuda/centro-ayuda',
            'support_contact' => 'contacto',
            'support_branches' => 'sucursales',
            'support_faq' => 'preguntas-frecuentes',
            'support_security' => 'seguridad',
            'support_complaints' => 'quejas',
            
            // Routes légales (LegalController)
            'legal_notices' => 'avisos-legales',
            'legal_terms' => 'terminos-condiciones',
            'legal_privacy' => 'politica-privacidad',
            'legal_cookies' => 'cookies',
        ],
    ];

    // Mapping des segments traduits pour certaines routes spécifiques
    private array $segmentTranslations = [
        'fr' => [
            'credit' => ['nl' => 'krediet', 'en' => 'credit', 'de' => 'kredit', 'es' => 'credito'],
            'offres' => ['nl' => 'aanbod', 'en' => 'offers', 'de' => 'angebote', 'es' => 'ofertas'],
            'offres-credit' => ['nl' => 'krediet-aanbod', 'en' => 'credit-offers', 'de' => 'kredit-angebote', 'es' => 'ofertas-credito'],
            'credit-personnel' => ['nl' => 'persoonlijke-lening', 'en' => 'personal-loan', 'de' => 'privatkredit', 'es' => 'prestamo-personal'],
            'credit-voyage' => ['nl' => 'reisgeld', 'en' => 'travel-loan', 'de' => 'reisekredit', 'es' => 'prestamo-viaje'],
            'credit-auto' => ['nl' => 'autolening', 'en' => 'auto-loan', 'de' => 'autokredit', 'es' => 'prestamo-auto'],
            'credit-immobilier' => ['nl' => 'hypotheek', 'en' => 'home-loan', 'de' => 'immobilienkredit', 'es' => 'prestamo-vivienda'],
            'hypothecaire' => ['nl' => 'hypothecair', 'en' => 'hypothecary', 'de' => 'hypothekar', 'es' => 'hipotecario'],
            'travaux' => ['nl' => 'verbouwing', 'en' => 'renovation', 'de' => 'renovierung', 'es' => 'reformas'],
            'amelioration-habitat' => ['nl' => 'woning-verbetering', 'en' => 'home-improvement', 'de' => 'wohnungsverbesserung', 'es' => 'mejoras-hogar'],
            'credit-relais' => ['nl' => 'overbruggingskredit', 'en' => 'bridge-loan', 'de' => 'zwischenfinanzierung', 'es' => 'credito-puente'],
            'leasing-automobile' => ['nl' => 'auto-lease', 'en' => 'car-leasing', 'de' => 'auto-leasing', 'es' => 'leasing-automovil'],
            'credit-consommation' => ['nl' => 'consumptie-krediet', 'en' => 'consumer-credit', 'de' => 'verbraucherdarlehen', 'es' => 'credito-consumo'],
            'regroupement-credits' => ['nl' => 'krediet-hergroepering', 'en' => 'debt-consolidation', 'de' => 'kreditumschuldung', 'es' => 'reunificacion-creditos'],
            'credit-renouvelable' => ['nl' => 'hernieuwbaar-krediet', 'en' => 'revolving-credit', 'de' => 'revolvierende-kredit', 'es' => 'credito-renovable'],
            'credit-professionnel' => ['nl' => 'zakelijk-krediet', 'en' => 'business-loan', 'de' => 'geschaeftskredit', 'es' => 'credito-profesional'],
            'credit-etudiant' => ['nl' => 'studenten-lening', 'en' => 'student-loan', 'de' => 'studienkredit', 'es' => 'credito-estudiante'],
            'microcredit' => ['nl' => 'microkrediet', 'en' => 'microcredit', 'de' => 'mikrokredit', 'es' => 'microcredito'],
            'demande' => ['nl' => 'aanvraag', 'en' => 'application', 'de' => 'antrag', 'es' => 'solicitud'],
            'credits' => ['nl' => 'kredieten', 'en' => 'credits', 'de' => 'kredite', 'es' => 'creditos'],
            'simulation-credit' => ['nl' => 'krediet-simulatie', 'en' => 'credit-simulation', 'de' => 'kredit-simulation', 'es' => 'simulacion-credito'],
            'calculer' => ['nl' => 'berekenen', 'en' => 'calculate', 'de' => 'berechnen', 'es' => 'calcular'],
            'resultat' => ['nl' => 'resultaat', 'en' => 'result', 'de' => 'ergebnis', 'es' => 'resultado'],
            'tableau-amortissement' => ['nl' => 'aflossingstabel', 'en' => 'amortization-table', 'de' => 'tilgungsplan', 'es' => 'tabla-amortizacion'],
            'comptes' => ['nl' => 'rekeningen', 'en' => 'accounts', 'de' => 'konten', 'es' => 'cuentas'],
            'cartes' => ['nl' => 'kaarten', 'en' => 'cards', 'de' => 'karten', 'es' => 'tarjetas'],
            'virements' => ['nl' => 'overboekingen', 'en' => 'transfers', 'de' => 'uberweisungen', 'es' => 'transferencias'],
            'transactions' => ['nl' => 'transacties', 'en' => 'transactions', 'de' => 'transaktionen', 'es' => 'transacciones'],
            'mes-beneficiaires' => ['nl' => 'mijn-begunstigden', 'en' => 'my-beneficiaries', 'de' => 'meine-begunstigen', 'es' => 'mis-beneficiarios'],
            'nouveau' => ['nl' => 'nieuw', 'en' => 'new', 'de' => 'neu', 'es' => 'nuevo'],
            'valider' => ['nl' => 'valideren', 'en' => 'validate', 'de' => 'validieren', 'es' => 'validar'],
            'details' => ['nl' => 'details', 'en' => 'details', 'de' => 'details', 'es' => 'detalles'],
            'annuler' => ['nl' => 'annuleren', 'en' => 'cancel', 'de' => 'stornieren', 'es' => 'cancelar'],
            'prets' => ['nl' => 'leningen', 'en' => 'loans', 'de' => 'darlehen', 'es' => 'prestamos'],
            'virement-credit' => ['nl' => 'krediet-overboeking', 'en' => 'credit-transfer', 'de' => 'kredit-uberweisung', 'es' => 'transferencia-credito'],
            'form' => ['nl' => 'form', 'en' => 'form', 'de' => 'form', 'es' => 'form'],
            'initiate' => ['nl' => 'initiate', 'en' => 'initiate', 'de' => 'initiate', 'es' => 'initiate'],
            'confirm' => ['nl' => 'confirm', 'en' => 'confirm', 'de' => 'confirm', 'es' => 'confirm'],
            'simulation' => ['nl' => 'simulatie', 'en' => 'simulation', 'de' => 'simulation', 'es' => 'simulacion'],
            'mobile-banking' => ['nl' => 'mobiel-bankieren', 'en' => 'mobile-banking', 'de' => 'mobiles-banking', 'es' => 'banca-movil'],
            'application-mobile' => ['nl' => 'mobiele-app', 'en' => 'mobile-app', 'de' => 'mobile-app', 'es' => 'aplicacion-movil'],
            'carte-credit' => ['nl' => 'kredietkaart', 'en' => 'credit-card', 'de' => 'kreditkarte', 'es' => 'tarjeta-credito'],
            'services' => ['nl' => 'services', 'en' => 'services', 'de' => 'services', 'es' => 'servicios'],
            'comptes-cartes' => ['nl' => 'rekeningen-kaarten', 'en' => 'accounts-cards', 'de' => 'konten-karten', 'es' => 'cuentas-tarjetas'],
            'epargne-placements' => ['nl' => 'sparen-beleggingen', 'en' => 'savings-investments', 'de' => 'sparen-investitionen', 'es' => 'ahorros-inversiones'],
            'assurances' => ['nl' => 'verzekeringen', 'en' => 'insurances', 'de' => 'versicherungen', 'es' => 'seguros'],
            'devis' => ['nl' => 'offerte', 'en' => 'quote', 'de' => 'angebot', 'es' => 'presupuesto'],
            'confirmation' => ['nl' => 'bevestiging', 'en' => 'confirmation', 'de' => 'bestaetigung', 'es' => 'confirmacion'],
            // Routes d'activation de compte
            'notification-activation' => ['nl' => 'activatie-melding', 'en' => 'activation-notice', 'de' => 'aktivierung-hinweis', 'es' => 'aviso-activacion'],
            'activer-compte' => ['nl' => 'account-activeren', 'en' => 'activate-account', 'de' => 'konto-aktivieren', 'es' => 'activar-cuenta'],
            // Routes profil et KYC
            'profil' => ['nl' => 'profiel', 'en' => 'profile', 'de' => 'profil', 'es' => 'perfil'],
            'modifier' => ['nl' => 'bewerken', 'en' => 'edit', 'de' => 'bearbeiten', 'es' => 'editar'],
            'preferences' => ['nl' => 'voorkeuren', 'en' => 'preferences', 'de' => 'einstellungen', 'es' => 'preferencias'],
            'kyc' => ['nl' => 'kyc', 'en' => 'kyc', 'de' => 'kyc', 'es' => 'kyc'],
            'soumettre' => ['nl' => 'indienen', 'en' => 'submit', 'de' => 'einreichen', 'es' => 'enviar'],
            'statut' => ['nl' => 'status', 'en' => 'status', 'de' => 'status', 'es' => 'estado'],
            'renvoyer-activation' => ['nl' => 'activatie-opnieuw-versturen', 'en' => 'resend-activation', 'de' => 'aktivierung-erneut-senden', 'es' => 'reenviar-activacion'],
            // Routes d'authentification
            'connexion' => ['nl' => 'inloggen', 'en' => 'login', 'de' => 'anmelden', 'es' => 'iniciar-sesion'],
            'deconnexion' => ['nl' => 'uitloggen', 'en' => 'logout', 'de' => 'abmelden', 'es' => 'cerrar-sesion'],
            'inscription' => ['nl' => 'registreren', 'en' => 'register', 'de' => 'registrieren', 'es' => 'registro'],
            // Routes banking principales
            'dashboard' => ['nl' => 'dashboard', 'en' => 'dashboard', 'de' => 'dashboard', 'es' => 'dashboard'],
            'epargne' => ['nl' => 'sparen', 'en' => 'savings', 'de' => 'sparen', 'es' => 'ahorros'],
            'overschrijvingen' => ['nl' => 'overboekingen', 'en' => 'transfers', 'de' => 'uberweisungen', 'es' => 'transferencias'],
            'souscrire' => ['nl' => 'abonneren', 'en' => 'subscribe', 'de' => 'abonnieren', 'es' => 'suscribir'],
            // Nouveaux segments pour les cartes
            'carte' => ['nl' => 'kaart', 'en' => 'card', 'de' => 'karte', 'es' => 'tarjeta'],
            'opposition' => ['nl' => 'blokkering', 'en' => 'opposition', 'de' => 'sperrung', 'es' => 'oposicion'],
            'urgence' => ['nl' => 'noodgeval', 'en' => 'emergency', 'de' => 'notfall', 'es' => 'emergencia'],
            // Routes de contrats
            'contrats' => ['nl' => 'contracten', 'en' => 'contracts', 'de' => 'vertrage', 'es' => 'contratos'],
            'signature' => ['nl' => 'handtekening', 'en' => 'signature', 'de' => 'unterschrift', 'es' => 'firma'],
            'signer' => ['nl' => 'ondertekenen', 'en' => 'sign', 'de' => 'unterschreiben', 'es' => 'firmar'],
            'succes' => ['nl' => 'succes', 'en' => 'success', 'de' => 'erfolg', 'es' => 'exito'],
            'telecharger' => ['nl' => 'downloaden', 'en' => 'download', 'de' => 'herunterladen', 'es' => 'descargar'],
            'telecharger' => ['nl' => 'downloaden', 'en' => 'download', 'de' => 'herunterladen', 'es' => 'descargar'],
            'voir' => ['nl' => 'bekijken', 'en' => 'view', 'de' => 'ansehen', 'es' => 'ver'],
            // Routes de demande de crédit
            'ma-demande-de-credit' => ['nl' => 'mijn-kredietaanvraag', 'en' => 'my-credit-application', 'de' => 'mein-kreditantrag', 'es' => 'mi-solicitud-credito'],
            'etape' => ['nl' => 'stap', 'en' => 'step', 'de' => 'schritt', 'es' => 'paso'],
            'recapitulatif' => ['nl' => 'samenvatting', 'en' => 'summary', 'de' => 'zusammenfassung', 'es' => 'resumen'],
            'soumettre' => ['nl' => 'indienen', 'en' => 'submit', 'de' => 'einreichen', 'es' => 'enviar'],
            // Segments d'erreur
            'erreur' => ['nl' => 'fout', 'en' => 'error', 'de' => 'fehler', 'es' => 'error'],
            'page-non-trouvee' => ['nl' => 'pagina-niet-gevonden', 'en' => 'page-not-found', 'de' => 'seite-nicht-gefunden', 'es' => 'pagina-no-encontrada'],
            'acces-interdit' => ['nl' => 'toegang-geweigerd', 'en' => 'access-denied', 'de' => 'zugriff-verweigert', 'es' => 'acceso-denegado'],
            'erreur-serveur' => ['nl' => 'server-fout', 'en' => 'server-error', 'de' => 'server-fehler', 'es' => 'error-servidor'],
            // Segments support
            'support' => ['nl' => 'support', 'en' => 'support', 'de' => 'support', 'es' => 'soporte'],
            'aide' => ['nl' => 'hulp', 'en' => 'help', 'de' => 'hilfe', 'es' => 'ayuda'],
            'centre-aide' => ['nl' => 'helpcentrum', 'en' => 'help-center', 'de' => 'hilfezentrum', 'es' => 'centro-ayuda'],
            'contact' => ['nl' => 'contact', 'en' => 'contact', 'de' => 'kontakt', 'es' => 'contacto'],
            'agences' => ['nl' => 'kantoren', 'en' => 'branches', 'de' => 'filialen', 'es' => 'sucursales'],
            'faq' => ['nl' => 'veelgestelde-vragen', 'en' => 'faq', 'de' => 'haeufige-fragen', 'es' => 'preguntas-frecuentes'],
            'securite' => ['nl' => 'beveiliging', 'en' => 'security', 'de' => 'sicherheit', 'es' => 'seguridad'],
            'reclamations' => ['nl' => 'klachten', 'en' => 'complaints', 'de' => 'beschwerden', 'es' => 'reclamaciones'],
            // Segments légaux
            'mentions-legales' => ['nl' => 'juridische-vermeldingen', 'en' => 'legal-notices', 'de' => 'rechtliche-hinweise', 'es' => 'avisos-legales'],
            'conditions-generales' => ['nl' => 'algemene-voorwaarden', 'en' => 'terms-conditions', 'de' => 'allgemeine-geschaeftsbedingungen', 'es' => 'terminos-condiciones'],
            'confidentialite' => ['nl' => 'privacy-beleid', 'en' => 'privacy-policy', 'de' => 'datenschutz', 'es' => 'politica-privacidad'],
            'cookies' => ['nl' => 'cookies', 'en' => 'cookies', 'de' => 'cookies', 'es' => 'cookies'],
            // Routes de réinitialisation de mot de passe
            'reinitialiser-mot-de-passe' => ['nl' => 'wachtwoord-resetten', 'en' => 'reset-password', 'de' => 'passwort-zurucksetzen', 'es' => 'restablecer-contraseña'],
            'verifier-email' => ['nl' => 'controleer-email', 'en' => 'check-email', 'de' => 'email-prufen', 'es' => 'verificar-email'],
            'reinitialiser' => ['nl' => 'resetten', 'en' => 'reset', 'de' => 'zurucksetzen', 'es' => 'restablecer'],
        ],
        'nl' => [
            'krediet' => ['fr' => 'credit', 'en' => 'credit', 'de' => 'kredit', 'es' => 'credito'],
            'aanbod' => ['fr' => 'offres', 'en' => 'offers', 'de' => 'angebote', 'es' => 'ofertas'],
            'krediet-aanbod' => ['fr' => 'offres-credit', 'en' => 'credit-offers', 'de' => 'kredit-angebote', 'es' => 'ofertas-credito'],
            'persoonlijke-lening' => ['fr' => 'credit-personnel', 'en' => 'personal-loan', 'de' => 'privatkredit', 'es' => 'prestamo-personal'],
            'reisgeld' => ['fr' => 'credit-voyage', 'en' => 'travel-loan', 'de' => 'reisekredit', 'es' => 'prestamo-viaje'],
            'autolening' => ['fr' => 'credit-auto', 'en' => 'auto-loan', 'de' => 'autokredit', 'es' => 'prestamo-auto'],
            'hypotheek' => ['fr' => 'credit-immobilier', 'en' => 'home-loan', 'de' => 'immobilienkredit', 'es' => 'prestamo-vivienda'],
            'hypothecair' => ['fr' => 'hypothecaire', 'en' => 'hypothecary', 'de' => 'hypothekar', 'es' => 'hipotecario'],
            'verbouwing' => ['fr' => 'travaux', 'en' => 'renovation', 'de' => 'renovierung', 'es' => 'reformas'],
            'woning-verbetering' => ['fr' => 'amelioration-habitat', 'en' => 'home-improvement', 'de' => 'wohnungsverbesserung', 'es' => 'mejoras-hogar'],
            'overbruggingskredit' => ['fr' => 'credit-relais', 'en' => 'bridge-loan', 'de' => 'zwischenfinanzierung', 'es' => 'credito-puente'],
            'auto-lease' => ['fr' => 'leasing-automobile', 'en' => 'car-leasing', 'de' => 'auto-leasing', 'es' => 'leasing-automovil'],
            'consumptie-krediet' => ['fr' => 'credit-consommation', 'en' => 'consumer-credit', 'de' => 'verbraucherdarlehen', 'es' => 'credito-consumo'],
            'krediet-hergroepering' => ['fr' => 'regroupement-credits', 'en' => 'debt-consolidation', 'de' => 'kreditumschuldung', 'es' => 'reunificacion-creditos'],
            'hernieuwbaar-krediet' => ['fr' => 'credit-renouvelable', 'en' => 'revolving-credit', 'de' => 'revolvierende-kredit', 'es' => 'credito-renovable'],
            'zakelijk-krediet' => ['fr' => 'credit-professionnel', 'en' => 'business-loan', 'de' => 'geschaeftskredit', 'es' => 'credito-profesional'],
            'studenten-lening' => ['fr' => 'credit-etudiant', 'en' => 'student-loan', 'de' => 'studienkredit', 'es' => 'credito-estudiante'],
            'microkrediet' => ['fr' => 'microcredit', 'en' => 'microcredit', 'de' => 'mikrokredit', 'es' => 'microcredito'],
            'aanvraag' => ['fr' => 'demande', 'en' => 'application', 'de' => 'antrag', 'es' => 'solicitud'],
            'kredieten' => ['fr' => 'credits', 'en' => 'credits', 'de' => 'kredite', 'es' => 'creditos'],
            'krediet-simulatie' => ['fr' => 'simulation-credit', 'en' => 'credit-simulation', 'de' => 'kredit-simulation', 'es' => 'simulacion-credito'],
            'berekenen' => ['fr' => 'calculer', 'en' => 'calculate', 'de' => 'berechnen', 'es' => 'calcular'],
            'resultaat' => ['fr' => 'resultat', 'en' => 'result', 'de' => 'ergebnis', 'es' => 'resultado'],
            'aflossingstabel' => ['fr' => 'tableau-amortissement', 'en' => 'amortization-table', 'de' => 'tilgungsplan', 'es' => 'tabla-amortizacion'],
            'rekeningen' => ['fr' => 'comptes', 'en' => 'accounts', 'de' => 'konten', 'es' => 'cuentas'],
            'kaarten' => ['fr' => 'cartes', 'en' => 'cards', 'de' => 'karten', 'es' => 'tarjetas'],
            'overboekingen' => ['fr' => 'virements', 'en' => 'transfers', 'de' => 'uberweisungen', 'es' => 'transferencias'],
            'abonneren' => ['fr' => 'souscrire', 'en' => 'subscribe', 'de' => 'abonnieren', 'es' => 'suscribir'],
            // Nouveaux segments pour les cartes
            'kaart' => ['fr' => 'carte', 'en' => 'card', 'de' => 'karte', 'es' => 'tarjeta'],
            'blokkering' => ['fr' => 'opposition', 'en' => 'opposition', 'de' => 'sperrung', 'es' => 'oposicion'],
            'noodgeval' => ['fr' => 'urgence', 'en' => 'emergency', 'de' => 'notfall', 'es' => 'emergencia'],
            'annuleren' => ['fr' => 'annuler', 'en' => 'cancel', 'de' => 'stornieren', 'es' => 'cancelar'],
            // Routes de contracten
            'contracten' => ['fr' => 'contrats', 'en' => 'contracts', 'de' => 'vertrage', 'es' => 'contratos'],
            'downloaden' => ['fr' => 'telecharger', 'en' => 'download', 'de' => 'herunterladen', 'es' => 'descargar'],
            'bekijken' => ['fr' => 'voir', 'en' => 'view', 'de' => 'ansehen', 'es' => 'ver'],
            // Routes de kredietaanvraag
            'mijn-kredietaanvraag' => ['fr' => 'ma-demande-de-credit', 'en' => 'my-credit-application', 'de' => 'mein-kreditantrag', 'es' => 'mi-solicitud-credito'],
            'stap' => ['fr' => 'etape', 'en' => 'step', 'de' => 'schritt', 'es' => 'paso'],
            'samenvatting' => ['fr' => 'recapitulatif', 'en' => 'summary', 'de' => 'zusammenfassung', 'es' => 'resumen'],
            'indienen' => ['fr' => 'soumettre', 'en' => 'submit', 'de' => 'einreichen', 'es' => 'enviar'],
            'bevestiging' => ['fr' => 'confirmation', 'en' => 'confirmation', 'de' => 'bestaetigung', 'es' => 'confirmacion'],
            'transacties' => ['fr' => 'transactions', 'en' => 'transactions', 'de' => 'transaktionen', 'es' => 'transacciones'],
            'mijn-begunstigden' => ['fr' => 'mes-beneficiaires', 'en' => 'my-beneficiaries', 'de' => 'meine-begunstigen', 'es' => 'mis-beneficiarios'],
            'nieuw' => ['fr' => 'nouveau', 'en' => 'new', 'de' => 'neu', 'es' => 'nuevo'],
            'valideren' => ['fr' => 'valider', 'en' => 'validate', 'de' => 'validieren', 'es' => 'validar'],
            'details' => ['fr' => 'details', 'en' => 'details', 'de' => 'details', 'es' => 'detalles'],
            'annuleren' => ['fr' => 'annuler', 'en' => 'cancel', 'de' => 'stornieren', 'es' => 'cancelar'],
            'leningen' => ['fr' => 'prets', 'en' => 'loans', 'de' => 'darlehen', 'es' => 'prestamos'],
            'krediet-overboeking' => ['fr' => 'virement-credit', 'en' => 'credit-transfer', 'de' => 'kredit-uberweisung', 'es' => 'transferencia-credito'],
            'form' => ['fr' => 'form', 'en' => 'form', 'de' => 'form', 'es' => 'form'],
            'initiate' => ['fr' => 'initiate', 'en' => 'initiate', 'de' => 'initiate', 'es' => 'initiate'],
            'confirm' => ['fr' => 'confirm', 'en' => 'confirm', 'de' => 'confirm', 'es' => 'confirm'],
            'simulatie' => ['fr' => 'simulation', 'en' => 'simulation', 'de' => 'simulation', 'es' => 'simulacion'],
            'mobiel-bankieren' => ['fr' => 'mobile-banking', 'en' => 'mobile-banking', 'de' => 'mobiles-banking', 'es' => 'banca-movil'],
            'mobiele-app' => ['fr' => 'application-mobile', 'en' => 'mobile-app', 'de' => 'mobile-app', 'es' => 'aplicacion-movil'],
            'kredietkaart' => ['fr' => 'carte-credit', 'en' => 'credit-card', 'de' => 'kreditkarte', 'es' => 'tarjeta-credito'],
            'services' => ['fr' => 'services', 'en' => 'services', 'de' => 'services', 'es' => 'servicios'],
            'rekeningen-kaarten' => ['fr' => 'comptes-cartes', 'en' => 'accounts-cards', 'de' => 'konten-karten', 'es' => 'cuentas-tarjetas'],
            'sparen-beleggingen' => ['fr' => 'epargne-placements', 'en' => 'savings-investments', 'de' => 'sparen-investitionen', 'es' => 'ahorros-inversiones'],
            'verzekeringen' => ['fr' => 'assurances', 'en' => 'insurances', 'de' => 'versicherungen', 'es' => 'seguros'],
            'offerte' => ['fr' => 'devis', 'en' => 'quote', 'de' => 'angebot', 'es' => 'presupuesto'],
            'bevestiging' => ['fr' => 'confirmation', 'en' => 'confirmation', 'de' => 'bestaetigung', 'es' => 'confirmacion'],
            // Routes d'activation de compte
            'activatie-melding' => ['fr' => 'notification-activation', 'en' => 'activation-notice', 'de' => 'aktivierung-hinweis', 'es' => 'aviso-activacion'],
            'account-activeren' => ['fr' => 'activer-compte', 'en' => 'activate-account', 'de' => 'konto-aktivieren', 'es' => 'activar-cuenta'],
            'activatie-opnieuw-versturen' => ['fr' => 'renvoyer-activation', 'en' => 'resend-activation', 'de' => 'aktivierung-erneut-senden', 'es' => 'reenviar-activacion'],
            // Routes d'authentification  
            'inloggen' => ['fr' => 'connexion', 'en' => 'login', 'de' => 'anmelden', 'es' => 'iniciar-sesion'],
            'uitloggen' => ['fr' => 'deconnexion', 'en' => 'logout', 'de' => 'abmelden', 'es' => 'cerrar-sesion'],
            'registreren' => ['fr' => 'inscription', 'en' => 'register', 'de' => 'registrieren', 'es' => 'registro'],
            // Routes banking principales
            'dashboard' => ['fr' => 'dashboard', 'en' => 'dashboard', 'de' => 'dashboard', 'es' => 'dashboard'],
            'sparen' => ['fr' => 'epargne', 'en' => 'savings', 'de' => 'sparen', 'es' => 'ahorros'],
            // Routes profiel et KYC
            'profiel' => ['fr' => 'profil', 'en' => 'profile', 'de' => 'profil', 'es' => 'perfil'],
            'bewerken' => ['fr' => 'modifier', 'en' => 'edit', 'de' => 'bearbeiten', 'es' => 'editar'],
            'voorkeuren' => ['fr' => 'preferences', 'en' => 'preferences', 'de' => 'einstellungen', 'es' => 'preferencias'],
            'kyc' => ['fr' => 'kyc', 'en' => 'kyc', 'de' => 'kyc', 'es' => 'kyc'],
            'indienen' => ['fr' => 'soumettre', 'en' => 'submit', 'de' => 'einreichen', 'es' => 'enviar'],
            'status' => ['fr' => 'statut', 'en' => 'status', 'de' => 'status', 'es' => 'estado'],
            // Segments d'erreur (depuis néerlandais)
            'fout' => ['fr' => 'erreur', 'en' => 'error', 'de' => 'fehler', 'es' => 'error'],
            'pagina-niet-gevonden' => ['fr' => 'page-non-trouvee', 'en' => 'page-not-found', 'de' => 'seite-nicht-gefunden', 'es' => 'pagina-no-encontrada'],
            'toegang-geweigerd' => ['fr' => 'acces-interdit', 'en' => 'access-denied', 'de' => 'zugriff-verweigert', 'es' => 'acceso-denegado'],
            'server-fout' => ['fr' => 'erreur-serveur', 'en' => 'server-error', 'de' => 'server-fehler', 'es' => 'error-servidor'],
            // Segments support (depuis néerlandais)
            'support' => ['fr' => 'support', 'en' => 'support', 'de' => 'support', 'es' => 'soporte'],
            'hulp' => ['fr' => 'aide', 'en' => 'help', 'de' => 'hilfe', 'es' => 'ayuda'],
            'helpcentrum' => ['fr' => 'centre-aide', 'en' => 'help-center', 'de' => 'hilfezentrum', 'es' => 'centro-ayuda'],
            'contact' => ['fr' => 'contact', 'en' => 'contact', 'de' => 'kontakt', 'es' => 'contacto'],
            'kantoren' => ['fr' => 'agences', 'en' => 'branches', 'de' => 'filialen', 'es' => 'sucursales'],
            'veelgestelde-vragen' => ['fr' => 'faq', 'en' => 'faq', 'de' => 'haeufige-fragen', 'es' => 'preguntas-frecuentes'],
            'beveiliging' => ['fr' => 'securite', 'en' => 'security', 'de' => 'sicherheit', 'es' => 'seguridad'],
            'klachten' => ['fr' => 'reclamations', 'en' => 'complaints', 'de' => 'beschwerden', 'es' => 'reclamaciones'],
            // Segments légaux (depuis néerlandais)
            'juridische-vermeldingen' => ['fr' => 'mentions-legales', 'en' => 'legal-notices', 'de' => 'rechtliche-hinweise', 'es' => 'avisos-legales'],
            'algemene-voorwaarden' => ['fr' => 'conditions-generales', 'en' => 'terms-conditions', 'de' => 'allgemeine-geschaeftsbedingungen', 'es' => 'terminos-condiciones'],
            'privacy-beleid' => ['fr' => 'confidentialite', 'en' => 'privacy-policy', 'de' => 'datenschutz', 'es' => 'politica-privacidad'],
            'cookies' => ['fr' => 'cookies', 'en' => 'cookies', 'de' => 'cookies', 'es' => 'cookies'],
            // Routes de réinitialisation de mot de passe
            'wachtwoord-resetten' => ['fr' => 'reinitialiser-mot-de-passe', 'en' => 'reset-password', 'de' => 'passwort-zurucksetzen', 'es' => 'restablecer-contraseña'],
            'controleer-email' => ['fr' => 'verifier-email', 'en' => 'check-email', 'de' => 'email-prufen', 'es' => 'verificar-email'],
            'resetten' => ['fr' => 'reinitialiser', 'en' => 'reset', 'de' => 'zurucksetzen', 'es' => 'restablecer'],
        ],
        'de' => [
            'kredit' => ['fr' => 'credit', 'nl' => 'krediet', 'en' => 'credit', 'es' => 'credito'],
            'angebote' => ['fr' => 'offres', 'nl' => 'aanbod', 'en' => 'offers', 'es' => 'ofertas'],
            'kredit-angebote' => ['fr' => 'offres-credit', 'nl' => 'krediet-aanbod', 'en' => 'credit-offers', 'es' => 'ofertas-credito'],
            'privatkredit' => ['fr' => 'credit-personnel', 'nl' => 'persoonlijke-lening', 'en' => 'personal-loan', 'es' => 'prestamo-personal'],
            'reisekredit' => ['fr' => 'credit-voyage', 'nl' => 'reisgeld', 'en' => 'travel-loan', 'es' => 'prestamo-viaje'],
            'autokredit' => ['fr' => 'credit-auto', 'nl' => 'autolening', 'en' => 'auto-loan', 'es' => 'prestamo-auto'],
            'immobilienkredit' => ['fr' => 'credit-immobilier', 'nl' => 'hypotheek', 'en' => 'home-loan', 'es' => 'prestamo-vivienda'],
            'hypothekar' => ['fr' => 'hypothecaire', 'nl' => 'hypothecair', 'en' => 'hypothecary', 'es' => 'hipotecario'],
            'renovierung' => ['fr' => 'travaux', 'nl' => 'verbouwing', 'en' => 'renovation', 'es' => 'reformas'],
            'wohnungsverbesserung' => ['fr' => 'amelioration-habitat', 'nl' => 'woning-verbetering', 'en' => 'home-improvement', 'es' => 'mejoras-hogar'],
            'zwischenfinanzierung' => ['fr' => 'credit-relais', 'nl' => 'overbruggingskredit', 'en' => 'bridge-loan', 'es' => 'credito-puente'],
            'auto-leasing' => ['fr' => 'leasing-automobile', 'nl' => 'auto-lease', 'en' => 'car-leasing', 'es' => 'leasing-automovil'],
            'verbraucherdarlehen' => ['fr' => 'credit-consommation', 'nl' => 'consumptie-krediet', 'en' => 'consumer-credit', 'es' => 'credito-consumo'],
            'kreditumschuldung' => ['fr' => 'regroupement-credits', 'nl' => 'krediet-hergroepering', 'en' => 'debt-consolidation', 'es' => 'reunificacion-creditos'],
            'revolvierende-kredit' => ['fr' => 'credit-renouvelable', 'nl' => 'hernieuwbaar-krediet', 'en' => 'revolving-credit', 'es' => 'credito-renovable'],
            'geschaeftskredit' => ['fr' => 'credit-professionnel', 'nl' => 'zakelijk-krediet', 'en' => 'business-loan', 'es' => 'credito-profesional'],
            'studienkredit' => ['fr' => 'credit-etudiant', 'nl' => 'studenten-lening', 'en' => 'student-loan', 'es' => 'credito-estudiante'],
            'mikrokredit' => ['fr' => 'microcredit', 'nl' => 'microkrediet', 'en' => 'microcredit', 'es' => 'microcredito'],
            // Segments principaux pour l'allemand
            'antrag' => ['fr' => 'demande', 'nl' => 'aanvraag', 'en' => 'application', 'es' => 'solicitud'],
            'kredite' => ['fr' => 'credits', 'nl' => 'kredieten', 'en' => 'credits', 'es' => 'creditos'],
            'kredit-simulation' => ['fr' => 'simulation-credit', 'nl' => 'krediet-simulatie', 'en' => 'credit-simulation', 'es' => 'simulacion-credito'],
            'berechnen' => ['fr' => 'calculer', 'nl' => 'berekenen', 'en' => 'calculate', 'es' => 'calcular'],
            'ergebnis' => ['fr' => 'resultat', 'nl' => 'resultaat', 'en' => 'result', 'es' => 'resultado'],
            'tilgungsplan' => ['fr' => 'tableau-amortissement', 'nl' => 'aflossingstabel', 'en' => 'amortization-table', 'es' => 'tabla-amortizacion'],
            'konten' => ['fr' => 'comptes', 'nl' => 'rekeningen', 'en' => 'accounts', 'es' => 'cuentas'],
            'karte' => ['fr' => 'carte', 'nl' => 'kaart', 'en' => 'card', 'es' => 'tarjeta'],
            'karten' => ['fr' => 'cartes', 'nl' => 'kaarten', 'en' => 'cards', 'es' => 'tarjetas'],
            'uberweisungen' => ['fr' => 'virements', 'nl' => 'overboekingen', 'en' => 'transfers', 'es' => 'transferencias'],
            'transaktionen' => ['fr' => 'transactions', 'nl' => 'transacties', 'en' => 'transactions', 'es' => 'transacciones'],
            'meine-begunstigten' => ['fr' => 'mes-beneficiaires', 'nl' => 'mijn-begunstigden', 'en' => 'my-beneficiaries', 'es' => 'mis-beneficiarios'],
            'neu' => ['fr' => 'nouveau', 'nl' => 'nieuw', 'en' => 'new', 'es' => 'nuevo'],
            'validieren' => ['fr' => 'valider', 'nl' => 'valideren', 'en' => 'validate', 'es' => 'validar'],
            'details' => ['fr' => 'details', 'nl' => 'details', 'en' => 'details', 'es' => 'detalles'],
            'stornieren' => ['fr' => 'annuler', 'nl' => 'annuleren', 'en' => 'cancel', 'es' => 'cancelar'],
            'darlehen' => ['fr' => 'prets', 'nl' => 'leningen', 'en' => 'loans', 'es' => 'prestamos'],
            'kredit-uberweisung' => ['fr' => 'virement-credit', 'nl' => 'krediet-overboeking', 'en' => 'credit-transfer', 'es' => 'transferencia-credito'],
            'form' => ['fr' => 'form', 'nl' => 'form', 'en' => 'form', 'es' => 'form'],
            'initiate' => ['fr' => 'initiate', 'nl' => 'initiate', 'en' => 'initiate', 'es' => 'initiate'],
            'confirm' => ['fr' => 'confirm', 'nl' => 'confirm', 'en' => 'confirm', 'es' => 'confirm'],
            'simulation' => ['fr' => 'simulation', 'nl' => 'simulatie', 'en' => 'simulation', 'es' => 'simulacion'],
            'mobiles-banking' => ['fr' => 'mobile-banking', 'nl' => 'mobiel-bankieren', 'en' => 'mobile-banking', 'es' => 'banca-movil'],
            'mobile-app' => ['fr' => 'application-mobile', 'nl' => 'mobiele-app', 'en' => 'mobile-app', 'es' => 'aplicacion-movil'],
            'kreditkarte' => ['fr' => 'carte-credit', 'nl' => 'kredietkaart', 'en' => 'credit-card', 'es' => 'tarjeta-credito'],
            'services' => ['fr' => 'services', 'nl' => 'services', 'en' => 'services', 'es' => 'servicios'],
            'konten-karten' => ['fr' => 'comptes-cartes', 'nl' => 'rekeningen-kaarten', 'en' => 'accounts-cards', 'es' => 'cuentas-tarjetas'],
            'sparen-investitionen' => ['fr' => 'epargne-placements', 'nl' => 'sparen-beleggingen', 'en' => 'savings-investments', 'es' => 'ahorros-inversiones'],
            'versicherungen' => ['fr' => 'assurances', 'nl' => 'verzekeringen', 'en' => 'insurances', 'es' => 'seguros'],
            'angebot' => ['fr' => 'devis', 'nl' => 'offerte', 'en' => 'quote', 'es' => 'presupuesto'],
            'bestaetigung' => ['fr' => 'confirmation', 'nl' => 'bevestiging', 'en' => 'confirmation', 'es' => 'confirmacion'],
            // Routes d'activation de compte
            'aktivierung-hinweis' => ['fr' => 'notification-activation', 'nl' => 'activatie-melding', 'en' => 'activation-notice', 'es' => 'aviso-activacion'],
            'konto-aktivieren' => ['fr' => 'activer-compte', 'nl' => 'account-activeren', 'en' => 'activate-account', 'es' => 'activar-cuenta'],
            'aktivierung-erneut-senden' => ['fr' => 'renvoyer-activation', 'nl' => 'activatie-opnieuw-versturen', 'en' => 'resend-activation', 'es' => 'reenviar-activacion'],
            // Routes d'authentification
            'anmelden' => ['fr' => 'connexion', 'nl' => 'inloggen', 'en' => 'login', 'es' => 'iniciar-sesion'],
            'abmelden' => ['fr' => 'deconnexion', 'nl' => 'uitloggen', 'en' => 'logout', 'es' => 'cerrar-sesion'],
            'registrieren' => ['fr' => 'inscription', 'nl' => 'registreren', 'en' => 'register', 'es' => 'registro'],
            // Routes banking principales
            'dashboard' => ['fr' => 'dashboard', 'nl' => 'dashboard', 'en' => 'dashboard', 'es' => 'dashboard'],
            'sparen' => ['fr' => 'epargne', 'nl' => 'sparen', 'en' => 'savings', 'es' => 'ahorros'],
            'abonnieren' => ['fr' => 'souscrire', 'nl' => 'abonneren', 'en' => 'subscribe', 'es' => 'suscribir'],
            'sperrung' => ['fr' => 'opposition', 'nl' => 'blokkering', 'en' => 'opposition', 'es' => 'oposicion'],
            'notfall' => ['fr' => 'urgence', 'nl' => 'noodgeval', 'en' => 'emergency', 'es' => 'emergencia'],
            'status' => ['fr' => 'statut', 'nl' => 'status', 'en' => 'status', 'es' => 'estado'],
            // Routes de contrats
            'vertrage' => ['fr' => 'contrats', 'nl' => 'contracten', 'en' => 'contracts', 'es' => 'contratos'],
            'herunterladen' => ['fr' => 'telecharger', 'nl' => 'downloaden', 'en' => 'download', 'es' => 'descargar'],
            'ansehen' => ['fr' => 'voir', 'nl' => 'bekijken', 'en' => 'view', 'es' => 'ver'],
            // Routes de demande de crédit
            'mein-kreditantrag' => ['fr' => 'ma-demande-de-credit', 'nl' => 'mijn-kredietaanvraag', 'en' => 'my-credit-application', 'es' => 'mi-solicitud-credito'],
            'schritt' => ['fr' => 'etape', 'nl' => 'stap', 'en' => 'step', 'es' => 'paso'],
            'zusammenfassung' => ['fr' => 'recapitulatif', 'nl' => 'samenvatting', 'en' => 'summary', 'es' => 'resumen'],
            'einreichen' => ['fr' => 'soumettre', 'nl' => 'indienen', 'en' => 'submit', 'es' => 'enviar'],
            // Routes profiel et KYC
            'profil' => ['fr' => 'profil', 'nl' => 'profiel', 'en' => 'profile', 'es' => 'perfil'],
            'bearbeiten' => ['fr' => 'modifier', 'nl' => 'bewerken', 'en' => 'edit', 'es' => 'editar'],
            'einstellungen' => ['fr' => 'preferences', 'nl' => 'voorkeuren', 'en' => 'preferences', 'es' => 'preferencias'],
            'kyc' => ['fr' => 'kyc', 'nl' => 'kyc', 'en' => 'kyc', 'es' => 'kyc'],
            // Segments d'erreur (depuis allemand)
            'fehler' => ['fr' => 'erreur', 'nl' => 'fout', 'en' => 'error', 'es' => 'error'],
            'seite-nicht-gefunden' => ['fr' => 'page-non-trouvee', 'nl' => 'pagina-niet-gevonden', 'en' => 'page-not-found', 'es' => 'pagina-no-encontrada'],
            'zugriff-verweigert' => ['fr' => 'acces-interdit', 'nl' => 'toegang-geweigerd', 'en' => 'access-denied', 'es' => 'acceso-denegado'],
            'server-fehler' => ['fr' => 'erreur-serveur', 'nl' => 'server-fout', 'en' => 'server-error', 'es' => 'error-servidor'],
            // Segments de support (depuis allemand)
            'hilfe' => ['fr' => 'aide', 'nl' => 'hulp', 'en' => 'help', 'es' => 'ayuda'],
            'hilfezentrum' => ['fr' => 'centre-aide', 'nl' => 'helpcentrum', 'en' => 'help-center', 'es' => 'centro-ayuda'],
            'kontakt' => ['fr' => 'contact', 'nl' => 'contact', 'en' => 'contact', 'es' => 'contacto'],
            'haeufige-fragen' => ['fr' => 'faq', 'nl' => 'veelgestelde-vragen', 'en' => 'faq', 'es' => 'preguntas-frecuentes'],
            'sicherheit' => ['fr' => 'securite', 'nl' => 'beveiliging', 'en' => 'security', 'es' => 'seguridad'],
            'beschwerden' => ['fr' => 'reclamations', 'nl' => 'klachten', 'en' => 'complaints', 'es' => 'reclamaciones'],
            'filialen' => ['fr' => 'agences', 'nl' => 'kantoren', 'en' => 'branches', 'es' => 'sucursales'],
            // Segments légaux (depuis allemand)
            'rechtliche-hinweise' => ['fr' => 'mentions-legales', 'nl' => 'juridische-vermeldingen', 'en' => 'legal-notices', 'es' => 'avisos-legales'],
            'allgemeine-geschaeftsbedingungen' => ['fr' => 'conditions-generales', 'nl' => 'algemene-voorwaarden', 'en' => 'terms-conditions', 'es' => 'terminos-condiciones'],
            'datenschutz' => ['fr' => 'confidentialite', 'nl' => 'privacy-beleid', 'en' => 'privacy-policy', 'es' => 'politica-privacidad'],
            'cookies' => ['fr' => 'cookies', 'nl' => 'cookies', 'en' => 'cookies', 'es' => 'cookies'],
            // Routes de réinitialisation de mot de passe
            'passwort-zurucksetzen' => ['fr' => 'reinitialiser-mot-de-passe', 'nl' => 'wachtwoord-resetten', 'en' => 'reset-password', 'es' => 'restablecer-contraseña'],
            'email-prufen' => ['fr' => 'verifier-email', 'nl' => 'controleer-email', 'en' => 'check-email', 'es' => 'verificar-email'],
            'zurucksetzen' => ['fr' => 'reinitialiser', 'nl' => 'resetten', 'en' => 'reset', 'es' => 'restablecer'],
        ],
        'en' => [
            'credit' => ['fr' => 'credit', 'nl' => 'krediet', 'de' => 'kredit', 'es' => 'credito'],
            'offers' => ['fr' => 'offres', 'nl' => 'aanbod', 'de' => 'angebote', 'es' => 'ofertas'],
            'credit-offers' => ['fr' => 'offres-credit', 'nl' => 'krediet-aanbod', 'de' => 'kredit-angebote', 'es' => 'ofertas-credito'],
            'personal-loan' => ['fr' => 'credit-personnel', 'nl' => 'persoonlijke-lening', 'de' => 'privatkredit', 'es' => 'prestamo-personal'],
            'travel-loan' => ['fr' => 'credit-voyage', 'nl' => 'reisgeld', 'de' => 'reisekredit', 'es' => 'prestamo-viaje'],
            'auto-loan' => ['fr' => 'credit-auto', 'nl' => 'autolening', 'de' => 'autokredit', 'es' => 'prestamo-auto'],
            'home-loan' => ['fr' => 'credit-immobilier', 'nl' => 'hypotheek', 'de' => 'immobilienkredit', 'es' => 'prestamo-vivienda'],
            'hypothecary' => ['fr' => 'hypothecaire', 'nl' => 'hypothecair', 'de' => 'hypothekar', 'es' => 'hipotecario'],
            'renovation' => ['fr' => 'travaux', 'nl' => 'verbouwing', 'de' => 'renovierung', 'es' => 'reformas'],
            'home-improvement' => ['fr' => 'amelioration-habitat', 'nl' => 'woning-verbetering', 'de' => 'wohnungsverbesserung', 'es' => 'mejoras-hogar'],
            'bridge-loan' => ['fr' => 'credit-relais', 'nl' => 'overbruggingskredit', 'de' => 'zwischenfinanzierung', 'es' => 'credito-puente'],
            'car-leasing' => ['fr' => 'leasing-automobile', 'nl' => 'auto-lease', 'de' => 'auto-leasing', 'es' => 'leasing-automovil'],
            'consumer-credit' => ['fr' => 'credit-consommation', 'nl' => 'consumptie-krediet', 'de' => 'verbraucherdarlehen', 'es' => 'credito-consumo'],
            'debt-consolidation' => ['fr' => 'regroupement-credits', 'nl' => 'krediet-hergroepering', 'de' => 'kreditumschuldung', 'es' => 'reunificacion-creditos'],
            'revolving-credit' => ['fr' => 'credit-renouvelable', 'nl' => 'hernieuwbaar-krediet', 'de' => 'revolvierende-kredit', 'es' => 'credito-renovable'],
            'business-loan' => ['fr' => 'credit-professionnel', 'nl' => 'zakelijk-krediet', 'de' => 'geschaeftskredit', 'es' => 'credito-profesional'],
            'student-loan' => ['fr' => 'credit-etudiant', 'nl' => 'studenten-lening', 'de' => 'studienkredit', 'es' => 'credito-estudiante'],
            'microcredit' => ['fr' => 'microcredit', 'nl' => 'microkrediet', 'de' => 'mikrokredit', 'es' => 'microcredito'],
            // Segments principaux pour l'anglais
            'application' => ['fr' => 'demande', 'nl' => 'aanvraag', 'de' => 'antrag', 'es' => 'solicitud'],
            'credits' => ['fr' => 'credits', 'nl' => 'kredieten', 'de' => 'kredite', 'es' => 'creditos'],
            'credit-simulation' => ['fr' => 'simulation-credit', 'nl' => 'krediet-simulatie', 'de' => 'kredit-simulation', 'es' => 'simulacion-credito'],
            'calculate' => ['fr' => 'calculer', 'nl' => 'berekenen', 'de' => 'berechnen', 'es' => 'calcular'],
            'result' => ['fr' => 'resultat', 'nl' => 'resultaat', 'de' => 'ergebnis', 'es' => 'resultado'],
            'amortization-table' => ['fr' => 'tableau-amortissement', 'nl' => 'aflossingstabel', 'de' => 'tilgungsplan', 'es' => 'tabla-amortizacion'],
            'accounts' => ['fr' => 'comptes', 'nl' => 'rekeningen', 'de' => 'konten', 'es' => 'cuentas'],
            'card' => ['fr' => 'carte', 'nl' => 'kaart', 'de' => 'karte', 'es' => 'tarjeta'],
            'cards' => ['fr' => 'cartes', 'nl' => 'kaarten', 'de' => 'karten', 'es' => 'tarjetas'],
            'transfers' => ['fr' => 'virements', 'nl' => 'overboekingen', 'de' => 'uberweisungen', 'es' => 'transferencias'],
            'transactions' => ['fr' => 'transactions', 'nl' => 'transacties', 'de' => 'transaktionen', 'es' => 'transacciones'],
            'my-beneficiaries' => ['fr' => 'mes-beneficiaires', 'nl' => 'mijn-begunstigden', 'de' => 'meine-begunstigten', 'es' => 'mis-beneficiarios'],
            'new' => ['fr' => 'nouveau', 'nl' => 'nieuw', 'de' => 'neu', 'es' => 'nuevo'],
            'validate' => ['fr' => 'valider', 'nl' => 'valideren', 'de' => 'validieren', 'es' => 'validar'],
            'details' => ['fr' => 'details', 'nl' => 'details', 'de' => 'details', 'es' => 'detalles'],
            'cancel' => ['fr' => 'annuler', 'nl' => 'annuleren', 'de' => 'stornieren', 'es' => 'cancelar'],
            'loans' => ['fr' => 'prets', 'nl' => 'leningen', 'de' => 'darlehen', 'es' => 'prestamos'],
            'credit-transfer' => ['fr' => 'virement-credit', 'nl' => 'krediet-overboeking', 'de' => 'kredit-uberweisung', 'es' => 'transferencia-credito'],
            'form' => ['fr' => 'form', 'nl' => 'form', 'de' => 'form', 'es' => 'form'],
            'initiate' => ['fr' => 'initiate', 'nl' => 'initiate', 'de' => 'initiate', 'es' => 'initiate'],
            'confirm' => ['fr' => 'confirm', 'nl' => 'confirm', 'de' => 'confirm', 'es' => 'confirm'],
            'simulation' => ['fr' => 'simulation', 'nl' => 'simulatie', 'de' => 'simulation', 'es' => 'simulacion'],
            'mobile-banking' => ['fr' => 'mobile-banking', 'nl' => 'mobiel-bankieren', 'de' => 'mobiles-banking', 'es' => 'banca-movil'],
            'mobile-app' => ['fr' => 'application-mobile', 'nl' => 'mobiele-app', 'de' => 'mobile-app', 'es' => 'aplicacion-movil'],
            'credit-card' => ['fr' => 'carte-credit', 'nl' => 'kredietkaart', 'de' => 'kreditkarte', 'es' => 'tarjeta-credito'],
            'services' => ['fr' => 'services', 'nl' => 'services', 'de' => 'services', 'es' => 'servicios'],
            'accounts-cards' => ['fr' => 'comptes-cartes', 'nl' => 'rekeningen-kaarten', 'de' => 'konten-karten', 'es' => 'cuentas-tarjetas'],
            'savings-investments' => ['fr' => 'epargne-placements', 'nl' => 'sparen-beleggingen', 'de' => 'sparen-investitionen', 'es' => 'ahorros-inversiones'],
            'insurances' => ['fr' => 'assurances', 'nl' => 'verzekeringen', 'de' => 'versicherungen', 'es' => 'seguros'],
            'quote' => ['fr' => 'devis', 'nl' => 'offerte', 'de' => 'angebot', 'es' => 'presupuesto'],
            'confirmation' => ['fr' => 'confirmation', 'nl' => 'bevestiging', 'de' => 'bestaetigung', 'es' => 'confirmacion'],
            // Routes d'activation de compte
            'activation-notice' => ['fr' => 'notification-activation', 'nl' => 'activatie-melding', 'de' => 'aktivierung-hinweis', 'es' => 'aviso-activacion'],
            'activate-account' => ['fr' => 'activer-compte', 'nl' => 'account-activeren', 'de' => 'konto-aktivieren', 'es' => 'activar-cuenta'],
            'resend-activation' => ['fr' => 'renvoyer-activation', 'nl' => 'activatie-opnieuw-versturen', 'de' => 'aktivierung-erneut-senden', 'es' => 'reenviar-activacion'],
            // Routes d'authentification
            'login' => ['fr' => 'connexion', 'nl' => 'inloggen', 'de' => 'anmelden', 'es' => 'iniciar-sesion'],
            'logout' => ['fr' => 'deconnexion', 'nl' => 'uitloggen', 'de' => 'abmelden', 'es' => 'cerrar-sesion'],
            'register' => ['fr' => 'inscription', 'nl' => 'registreren', 'de' => 'registrieren', 'es' => 'registro'],
            // Routes banking principales
            'dashboard' => ['fr' => 'dashboard', 'nl' => 'dashboard', 'de' => 'dashboard', 'es' => 'dashboard'],
            'savings' => ['fr' => 'epargne', 'nl' => 'sparen', 'de' => 'sparen', 'es' => 'ahorros'],
            'subscribe' => ['fr' => 'souscrire', 'nl' => 'abonneren', 'de' => 'abonnieren', 'es' => 'suscribir'],
            'opposition' => ['fr' => 'opposition', 'nl' => 'blokkering', 'de' => 'sperrung', 'es' => 'oposicion'],
            'emergency' => ['fr' => 'urgence', 'nl' => 'noodgeval', 'de' => 'notfall', 'es' => 'emergencia'],
            'status' => ['fr' => 'statut', 'nl' => 'status', 'de' => 'status', 'es' => 'estado'],
            // Routes de contrats
            'contracts' => ['fr' => 'contrats', 'nl' => 'contracten', 'de' => 'vertrage', 'es' => 'contratos'],
            'download' => ['fr' => 'telecharger', 'nl' => 'downloaden', 'de' => 'herunterladen', 'es' => 'descargar'],
            'view' => ['fr' => 'voir', 'nl' => 'bekijken', 'de' => 'ansehen', 'es' => 'ver'],
            // Routes de demande de crédit
            'my-credit-application' => ['fr' => 'ma-demande-de-credit', 'nl' => 'mijn-kredietaanvraag', 'de' => 'mein-kreditantrag', 'es' => 'mi-solicitud-credito'],
            'step' => ['fr' => 'etape', 'nl' => 'stap', 'de' => 'schritt', 'es' => 'paso'],
            'summary' => ['fr' => 'recapitulatif', 'nl' => 'samenvatting', 'de' => 'zusammenfassung', 'es' => 'resumen'],
            'submit' => ['fr' => 'soumettre', 'nl' => 'indienen', 'de' => 'einreichen', 'es' => 'enviar'],
            // Routes profiel et KYC
            'profile' => ['fr' => 'profil', 'nl' => 'profiel', 'de' => 'profil', 'es' => 'perfil'],
            'edit' => ['fr' => 'modifier', 'nl' => 'bewerken', 'de' => 'bearbeiten', 'es' => 'editar'],
            'preferences' => ['fr' => 'preferences', 'nl' => 'voorkeuren', 'de' => 'einstellungen', 'es' => 'preferencias'],
            'kyc' => ['fr' => 'kyc', 'nl' => 'kyc', 'de' => 'kyc', 'es' => 'kyc'],
            // Segments d'erreur (depuis anglais)
            'error' => ['fr' => 'erreur', 'nl' => 'fout', 'de' => 'fehler', 'es' => 'error'],
            'page-not-found' => ['fr' => 'page-non-trouvee', 'nl' => 'pagina-niet-gevonden', 'de' => 'seite-nicht-gefunden', 'es' => 'pagina-no-encontrada'],
            'access-denied' => ['fr' => 'acces-interdit', 'nl' => 'toegang-geweigerd', 'de' => 'zugriff-verweigert', 'es' => 'acceso-denegado'],
            'server-error' => ['fr' => 'erreur-serveur', 'nl' => 'server-fout', 'de' => 'server-fehler', 'es' => 'error-servidor'],
            // Segments légaux (depuis anglais)
            'legal-notices' => ['fr' => 'mentions-legales', 'nl' => 'juridische-vermeldingen', 'de' => 'rechtliche-hinweise', 'es' => 'avisos-legales'],
            'terms-conditions' => ['fr' => 'conditions-generales', 'nl' => 'algemene-voorwaarden', 'de' => 'allgemeine-geschaeftsbedingungen', 'es' => 'terminos-condiciones'],
            'privacy-policy' => ['fr' => 'confidentialite', 'nl' => 'privacy-beleid', 'de' => 'datenschutz', 'es' => 'politica-privacidad'],
            'cookies' => ['fr' => 'cookies', 'nl' => 'cookies', 'de' => 'cookies', 'es' => 'cookies'],
            // Routes de réinitialisation de mot de passe
            'reset-password' => ['fr' => 'reinitialiser-mot-de-passe', 'nl' => 'wachtwoord-resetten', 'de' => 'passwort-zurucksetzen', 'es' => 'restablecer-contraseña'],
            'check-email' => ['fr' => 'verifier-email', 'nl' => 'controleer-email', 'de' => 'email-prufen', 'es' => 'verificar-email'],
            // Segments anglais supplémentaires basés sur l'analyse des contrôleurs
            'quote' => ['fr' => 'devis', 'nl' => 'offerte', 'de' => 'angebot', 'es' => 'cotizacion'],
            'signature' => ['fr' => 'signature', 'nl' => 'handtekening', 'de' => 'unterschrift', 'es' => 'firma'],
            'sign' => ['fr' => 'signer', 'nl' => 'ondertekenen', 'de' => 'unterschreiben', 'es' => 'firmar'],
            'success' => ['fr' => 'succes', 'nl' => 'succes', 'de' => 'erfolg', 'es' => 'exito'],
            'change-password' => ['fr' => 'changer-mot-de-passe', 'nl' => 'wachtwoord-wijzigen', 'de' => 'passwort-andern', 'es' => 'cambiar-contraseña'],
            'security' => ['fr' => 'securite', 'nl' => 'beveiliging', 'de' => 'sicherheit', 'es' => 'seguridad'],
            'complaints' => ['fr' => 'reclamations', 'nl' => 'klachten', 'de' => 'beschwerden', 'es' => 'quejas'],
            'branches' => ['fr' => 'agences', 'nl' => 'kantoren', 'de' => 'filialen', 'es' => 'sucursales'],
            'help-center' => ['fr' => 'centre-aide', 'nl' => 'helpcentrum', 'de' => 'hilfezentrum', 'es' => 'centro-ayuda'],
            'help' => ['fr' => 'aide', 'nl' => 'hulp', 'de' => 'hilfe', 'es' => 'ayuda'],
            'support' => ['fr' => 'support', 'nl' => 'support', 'de' => 'support', 'es' => 'soporte'],
            'loans' => ['fr' => 'prets', 'nl' => 'leningen', 'de' => 'darlehen', 'es' => 'prestamos'],
            'credit-application' => ['fr' => 'demande-credit', 'nl' => 'krediet-aanvraag', 'de' => 'kreditantrag', 'es' => 'solicitud-credito'],
            'credit-transfer' => ['fr' => 'virement-credit', 'nl' => 'krediet-overboeking', 'de' => 'kredit-uberweisung', 'es' => 'transferencia-credito'],
            'subscribe' => ['fr' => 'souscrire', 'nl' => 'abonneren', 'de' => 'abonnieren', 'es' => 'suscribir'],
            'new' => ['fr' => 'nouveau', 'nl' => 'nieuw', 'de' => 'neu', 'es' => 'nuevo'],
            'confirmation' => ['fr' => 'confirmation', 'nl' => 'bevestiging', 'de' => 'bestaetigung', 'es' => 'confirmacion'],
            'application' => ['fr' => 'demande', 'nl' => 'aanvraag', 'de' => 'antrag', 'es' => 'solicitud'],
            'reset' => ['fr' => 'reinitialiser', 'nl' => 'resetten', 'de' => 'zurucksetzen', 'es' => 'restablecer'],
        ],
        
        'es' => [
            'credito' => ['fr' => 'credit', 'nl' => 'krediet', 'de' => 'kredit', 'en' => 'credit'],
            'ofertas' => ['fr' => 'offres', 'nl' => 'aanbod', 'de' => 'angebote', 'en' => 'offers'],
            'ofertas-credito' => ['fr' => 'offres-credit', 'nl' => 'krediet-aanbod', 'de' => 'kredit-angebote', 'en' => 'credit-offers'],
            'prestamo-personal' => ['fr' => 'credit-personnel', 'nl' => 'persoonlijke-lening', 'de' => 'privatkredit', 'en' => 'personal-loan'],
            'prestamo-viaje' => ['fr' => 'credit-voyage', 'nl' => 'reisgeld', 'de' => 'reisekredit', 'en' => 'travel-loan'],
            'prestamo-auto' => ['fr' => 'credit-auto', 'nl' => 'autolening', 'de' => 'autokredit', 'en' => 'auto-loan'],
            'prestamo-vivienda' => ['fr' => 'credit-immobilier', 'nl' => 'hypotheek', 'de' => 'immobilienkredit', 'en' => 'home-loan'],
            'hipotecario' => ['fr' => 'hypothecaire', 'nl' => 'hypothecair', 'de' => 'hypothekar', 'en' => 'hypothecary'],
            'reformas' => ['fr' => 'travaux', 'nl' => 'verbouwing', 'de' => 'renovierung', 'en' => 'renovation'],
            'mejoras-hogar' => ['fr' => 'amelioration-habitat', 'nl' => 'woning-verbetering', 'de' => 'wohnungsverbesserung', 'en' => 'home-improvement'],
            'credito-puente' => ['fr' => 'credit-relais', 'nl' => 'overbruggingskredit', 'de' => 'zwischenfinanzierung', 'en' => 'bridge-loan'],
            'leasing-automovil' => ['fr' => 'leasing-automobile', 'nl' => 'auto-lease', 'de' => 'auto-leasing', 'en' => 'car-leasing'],
            'credito-consumo' => ['fr' => 'credit-consommation', 'nl' => 'consumptie-krediet', 'de' => 'verbraucherdarlehen', 'en' => 'consumer-credit'],
            'reunificacion-creditos' => ['fr' => 'regroupement-credits', 'nl' => 'krediet-hergroepering', 'de' => 'kreditumschuldung', 'en' => 'debt-consolidation'],
            'credito-renovable' => ['fr' => 'credit-renouvelable', 'nl' => 'hernieuwbaar-krediet', 'de' => 'revolvierende-kredit', 'en' => 'revolving-credit'],
            'credito-profesional' => ['fr' => 'credit-professionnel', 'nl' => 'zakelijk-krediet', 'de' => 'geschaeftskredit', 'en' => 'business-loan'],
            'credito-estudiante' => ['fr' => 'credit-etudiant', 'nl' => 'studenten-lening', 'de' => 'studienkredit', 'en' => 'student-loan'],
            'microcredito' => ['fr' => 'microcredit', 'nl' => 'microkrediet', 'de' => 'mikrokredit', 'en' => 'microcredit'],
            // Segments principaux pour l'espagnol
            'solicitud' => ['fr' => 'demande', 'nl' => 'aanvraag', 'de' => 'antrag', 'en' => 'application'],
            'creditos' => ['fr' => 'credits', 'nl' => 'kredieten', 'de' => 'kredite', 'en' => 'credits'],
            'simulacion-credito' => ['fr' => 'simulation-credit', 'nl' => 'krediet-simulatie', 'de' => 'kredit-simulation', 'en' => 'credit-simulation'],
            'calcular' => ['fr' => 'calculer', 'nl' => 'berekenen', 'de' => 'berechnen', 'en' => 'calculate'],
            'resultado' => ['fr' => 'resultat', 'nl' => 'resultaat', 'de' => 'ergebnis', 'en' => 'result'],
            'tabla-amortizacion' => ['fr' => 'tableau-amortissement', 'nl' => 'aflossingstabel', 'de' => 'tilgungsplan', 'en' => 'amortization-table'],
            'cuentas' => ['fr' => 'comptes', 'nl' => 'rekeningen', 'de' => 'konten', 'en' => 'accounts'],
            'tarjeta' => ['fr' => 'carte', 'nl' => 'kaart', 'de' => 'karte', 'en' => 'card'],
            'tarjetas' => ['fr' => 'cartes', 'nl' => 'kaarten', 'de' => 'karten', 'en' => 'cards'],
            'transferencias' => ['fr' => 'virements', 'nl' => 'overboekingen', 'de' => 'uberweisungen', 'en' => 'transfers'],
            'transacciones' => ['fr' => 'transactions', 'nl' => 'transacties', 'de' => 'transaktionen', 'en' => 'transactions'],
            'mis-beneficiarios' => ['fr' => 'mes-beneficiaires', 'nl' => 'mijn-begunstigden', 'de' => 'meine-begunstigten', 'en' => 'my-beneficiaries'],
            'nuevo' => ['fr' => 'nouveau', 'nl' => 'nieuw', 'de' => 'neu', 'en' => 'new'],
            'validar' => ['fr' => 'valider', 'nl' => 'valideren', 'de' => 'validieren', 'en' => 'validate'],
            'detalles' => ['fr' => 'details', 'nl' => 'details', 'de' => 'details', 'en' => 'details'],
            'cancelar' => ['fr' => 'annuler', 'nl' => 'annuleren', 'de' => 'stornieren', 'en' => 'cancel'],
            'prestamos' => ['fr' => 'prets', 'nl' => 'leningen', 'de' => 'darlehen', 'en' => 'loans'],
            'transferencia-credito' => ['fr' => 'virement-credit', 'nl' => 'krediet-overboeking', 'de' => 'kredit-uberweisung', 'en' => 'credit-transfer'],
            'form' => ['fr' => 'form', 'nl' => 'form', 'de' => 'form', 'en' => 'form'],
            'initiate' => ['fr' => 'initiate', 'nl' => 'initiate', 'de' => 'initiate', 'en' => 'initiate'],
            'confirm' => ['fr' => 'confirm', 'nl' => 'confirm', 'de' => 'confirm', 'en' => 'confirm'],
            'simulacion' => ['fr' => 'simulation', 'nl' => 'simulatie', 'de' => 'simulation', 'en' => 'simulation'],
            'banca-movil' => ['fr' => 'mobile-banking', 'nl' => 'mobiel-bankieren', 'de' => 'mobiles-banking', 'en' => 'mobile-banking'],
            'aplicacion-movil' => ['fr' => 'application-mobile', 'nl' => 'mobiele-app', 'de' => 'mobile-app', 'en' => 'mobile-app'],
            'tarjeta-credito' => ['fr' => 'carte-credit', 'nl' => 'kredietkaart', 'de' => 'kreditkarte', 'en' => 'credit-card'],
            'servicios' => ['fr' => 'services', 'nl' => 'services', 'de' => 'services', 'en' => 'services'],
            'cuentas-tarjetas' => ['fr' => 'comptes-cartes', 'nl' => 'rekeningen-kaarten', 'de' => 'konten-karten', 'en' => 'accounts-cards'],
            'ahorros-inversiones' => ['fr' => 'epargne-placements', 'nl' => 'sparen-beleggingen', 'de' => 'sparen-investitionen', 'en' => 'savings-investments'],
            'seguros' => ['fr' => 'assurances', 'nl' => 'verzekeringen', 'de' => 'versicherungen', 'en' => 'insurances'],
            'presupuesto' => ['fr' => 'devis', 'nl' => 'offerte', 'de' => 'angebot', 'en' => 'quote'],
            'confirmacion' => ['fr' => 'confirmation', 'nl' => 'bevestiging', 'de' => 'bestaetigung', 'en' => 'confirmation'],
            // Routes d'activation de compte
            'aviso-activacion' => ['fr' => 'notification-activation', 'nl' => 'activatie-melding', 'de' => 'aktivierung-hinweis', 'en' => 'activation-notice'],
            'activar-cuenta' => ['fr' => 'activer-compte', 'nl' => 'account-activeren', 'de' => 'konto-aktivieren', 'en' => 'activate-account'],
            'reenviar-activacion' => ['fr' => 'renvoyer-activation', 'nl' => 'activatie-opnieuw-versturen', 'de' => 'aktivierung-erneut-senden', 'en' => 'resend-activation'],
            // Routes d'authentification
            'iniciar-sesion' => ['fr' => 'connexion', 'nl' => 'inloggen', 'de' => 'anmelden', 'en' => 'login'],
            'cerrar-sesion' => ['fr' => 'deconnexion', 'nl' => 'uitloggen', 'de' => 'abmelden', 'en' => 'logout'],
            'registro' => ['fr' => 'inscription', 'nl' => 'registreren', 'de' => 'registrieren', 'en' => 'register'],
            // Routes banking principales
            'dashboard' => ['fr' => 'dashboard', 'nl' => 'dashboard', 'de' => 'dashboard', 'en' => 'dashboard'],
            'ahorros' => ['fr' => 'epargne', 'nl' => 'sparen', 'de' => 'sparen', 'en' => 'savings'],
            'suscribir' => ['fr' => 'souscrire', 'nl' => 'abonneren', 'de' => 'abonnieren', 'en' => 'subscribe'],
            'oposicion' => ['fr' => 'opposition', 'nl' => 'blokkering', 'de' => 'sperrung', 'en' => 'opposition'],
            'emergencia' => ['fr' => 'urgence', 'nl' => 'noodgeval', 'de' => 'notfall', 'en' => 'emergency'],
            'estado' => ['fr' => 'statut', 'nl' => 'status', 'de' => 'status', 'en' => 'status'],
            // Routes de contrats
            'contratos' => ['fr' => 'contrats', 'nl' => 'contracten', 'de' => 'vertrage', 'en' => 'contracts'],
            'descargar' => ['fr' => 'telecharger', 'nl' => 'downloaden', 'de' => 'herunterladen', 'en' => 'download'],
            'ver' => ['fr' => 'voir', 'nl' => 'bekijken', 'de' => 'ansehen', 'en' => 'view'],
            // Routes de demande de crédit
            'mi-solicitud-credito' => ['fr' => 'ma-demande-de-credit', 'nl' => 'mijn-kredietaanvraag', 'de' => 'mein-kreditantrag', 'en' => 'my-credit-application'],
            'paso' => ['fr' => 'etape', 'nl' => 'stap', 'de' => 'schritt', 'en' => 'step'],
            'resumen' => ['fr' => 'recapitulatif', 'nl' => 'samenvatting', 'de' => 'zusammenfassung', 'en' => 'summary'],
            'enviar' => ['fr' => 'soumettre', 'nl' => 'indienen', 'de' => 'einreichen', 'en' => 'submit'],
            // Routes profiel et KYC
            'perfil' => ['fr' => 'profil', 'nl' => 'profiel', 'de' => 'profil', 'en' => 'profile'],
            'editar' => ['fr' => 'modifier', 'nl' => 'bewerken', 'de' => 'bearbeiten', 'en' => 'edit'],
            'preferencias' => ['fr' => 'preferences', 'nl' => 'voorkeuren', 'de' => 'einstellungen', 'en' => 'preferences'],
            'kyc' => ['fr' => 'kyc', 'nl' => 'kyc', 'de' => 'kyc', 'en' => 'kyc'],
            // Segments d'erreur (depuis espagnol)
            'error' => ['fr' => 'erreur', 'nl' => 'fout', 'de' => 'fehler', 'en' => 'error'],
            'pagina-no-encontrada' => ['fr' => 'page-non-trouvee', 'nl' => 'pagina-niet-gevonden', 'de' => 'seite-nicht-gefunden', 'en' => 'page-not-found'],
            'acceso-denegado' => ['fr' => 'acces-interdit', 'nl' => 'toegang-geweigerd', 'de' => 'zugriff-verweigert', 'en' => 'access-denied'],
            'error-servidor' => ['fr' => 'erreur-serveur', 'nl' => 'server-fout', 'de' => 'server-fehler', 'en' => 'server-error'],
            // Segments légaux (depuis espagnol)
            'avisos-legales' => ['fr' => 'mentions-legales', 'nl' => 'juridische-vermeldingen', 'de' => 'rechtliche-hinweise', 'en' => 'legal-notices'],
            'terminos-condiciones' => ['fr' => 'conditions-generales', 'nl' => 'algemene-voorwaarden', 'de' => 'allgemeine-geschaeftsbedingungen', 'en' => 'terms-conditions'],
            'politica-privacidad' => ['fr' => 'confidentialite', 'nl' => 'privacy-beleid', 'de' => 'datenschutz', 'en' => 'privacy-policy'],
            'cookies' => ['fr' => 'cookies', 'nl' => 'cookies', 'de' => 'cookies', 'en' => 'cookies'],
            // Routes de réinitialisation de mot de passe
            'restablecer-contraseña' => ['fr' => 'reinitialiser-mot-de-passe', 'nl' => 'wachtwoord-resetten', 'de' => 'passwort-zurucksetzen', 'en' => 'reset-password'],
            'verificar-email' => ['fr' => 'verifier-email', 'nl' => 'controleer-email', 'de' => 'email-prufen', 'en' => 'check-email'],
            // Segments espagnols supplémentaires basés sur l'analyse des contrôleurs
            'cotizacion' => ['fr' => 'devis', 'nl' => 'offerte', 'de' => 'angebot', 'en' => 'quote'],
            'firma' => ['fr' => 'signature', 'nl' => 'handtekening', 'de' => 'unterschrift', 'en' => 'signature'],
            'firmar' => ['fr' => 'signer', 'nl' => 'ondertekenen', 'de' => 'unterschreiben', 'en' => 'sign'],
            'exito' => ['fr' => 'succes', 'nl' => 'succes', 'de' => 'erfolg', 'en' => 'success'],
            'cambiar-contraseña' => ['fr' => 'changer-mot-de-passe', 'nl' => 'wachtwoord-wijzigen', 'de' => 'passwort-andern', 'en' => 'change-password'],
            'seguridad' => ['fr' => 'securite', 'nl' => 'beveiliging', 'de' => 'sicherheit', 'en' => 'security'],
            'quejas' => ['fr' => 'reclamations', 'nl' => 'klachten', 'de' => 'beschwerden', 'en' => 'complaints'],
            'sucursales' => ['fr' => 'agences', 'nl' => 'kantoren', 'de' => 'filialen', 'en' => 'branches'],
            'centro-ayuda' => ['fr' => 'centre-aide', 'nl' => 'helpcentrum', 'de' => 'hilfezentrum', 'en' => 'help-center'],
            'ayuda' => ['fr' => 'aide', 'nl' => 'hulp', 'de' => 'hilfe', 'en' => 'help'],
            'soporte' => ['fr' => 'support', 'nl' => 'support', 'de' => 'support', 'en' => 'support'],
            'prestamos' => ['fr' => 'prets', 'nl' => 'leningen', 'de' => 'darlehen', 'en' => 'loans'],
            'solicitud-credito' => ['fr' => 'demande-credit', 'nl' => 'krediet-aanvraag', 'de' => 'kreditantrag', 'en' => 'credit-application'],
            'transferencia-credito' => ['fr' => 'virement-credit', 'nl' => 'krediet-overboeking', 'de' => 'kredit-uberweisung', 'en' => 'credit-transfer'],
            'suscribir' => ['fr' => 'souscrire', 'nl' => 'abonneren', 'de' => 'abonnieren', 'en' => 'subscribe'],
            'nueva' => ['fr' => 'nouvelle', 'nl' => 'nieuwe', 'de' => 'neue', 'en' => 'new'],
            'confirmacion' => ['fr' => 'confirmation', 'nl' => 'bevestiging', 'de' => 'bestaetigung', 'en' => 'confirmation'],
            'restablecer' => ['fr' => 'reinitialiser', 'nl' => 'resetten', 'de' => 'zurucksetzen', 'en' => 'reset'],
        ],
    ];

    public function getLocalizedPath(string $routeKey, string $locale): string
    {
        return $this->routeTranslations[$locale][$routeKey] ?? $routeKey;
    }

    public function getRouteKeyFromPath(string $path, string $locale): ?string
    {
        $localizedRoutes = $this->routeTranslations[$locale] ?? [];
        
        foreach ($localizedRoutes as $routeKey => $localizedPath) {
            if ($localizedPath === trim($path, '/')) {
                return $routeKey;
            }
        }
        
        return null;
    }

    public function switchLocaleInUrl(string $currentUrl, string $newLocale): string
    {
        $urlParts = parse_url($currentUrl);
        $path = $urlParts['path'] ?? '/';
        $query = $urlParts['query'] ?? '';
        
        // Vérifier si c'est une route de changement de langue (éviter les boucles)
        if (strpos($path, '/change-language/') !== false) {
            // Rediriger vers l'accueil avec la nouvelle locale
            return "/{$newLocale}";
        }
        
        // Extraire la locale actuelle et le chemin
        if (preg_match('#^/([a-z]{2})(/.*)?$#', $path, $matches)) {
            $currentLocale = $matches[1];
            $routePath = trim($matches[2] ?? '', '/');
            
            // Si pas de chemin spécifique, retourner juste la locale
            if (empty($routePath)) {
                return "/{$newLocale}";
            }
            
            // Diviser le chemin en segments
            $segments = explode('/', $routePath);
            
            // Traduire chaque segment individuellement
            $translatedSegments = [];
            foreach ($segments as $segment) {
                // Ignorer les segments vides
                if (empty($segment)) {
                    continue;
                }
                
                // Ignorer les segments numériques (IDs)
                if (is_numeric($segment)) {
                    $translatedSegments[] = $segment;
                    continue;
                }
                
                // Chercher dans les traductions de segments
                if (isset($this->segmentTranslations[$currentLocale][$segment])) {
                    $translatedSegments[] = $this->segmentTranslations[$currentLocale][$segment][$newLocale] ?? $segment;
                } else {
                    $translatedSegments[] = $segment;
                }
            }
            
            // Reconstruire l'URL avec les segments traduits
            $translatedPath = implode('/', $translatedSegments);
            $newUrl = "/{$newLocale}" . ($translatedPath ? "/{$translatedPath}" : '');
            
            // Ajouter les paramètres de requête s'ils existent
            if (!empty($query)) {
                $newUrl .= '?' . $query;
            }
            
            return $newUrl;
        }
        
        // Fallback : remplacer simplement la locale et préserver les paramètres
        $newPath = preg_replace('#^/[a-z]{2}#', "/{$newLocale}", $path);
        return $newPath . (!empty($query) ? '?' . $query : '');
    }

    public function getAvailableLocales(): array
    {
        return array_keys($this->routeTranslations);
    }

    public function isValidLocale(string $locale): bool
    {
        return isset($this->routeTranslations[$locale]);
    }
}
