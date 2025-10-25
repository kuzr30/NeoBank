-- Script SQL pour vider toutes les tables sauf company_settings
-- Désactiver les contraintes de clés étrangères temporairement
SET FOREIGN_KEY_CHECKS = 0;

-- Vider toutes les tables sauf company_settings
TRUNCATE TABLE accounts;
TRUNCATE TABLE amortization_schedule;
TRUNCATE TABLE bank_accounts;
TRUNCATE TABLE card_oppositions;
TRUNCATE TABLE card_subscriptions;
TRUNCATE TABLE cards;
TRUNCATE TABLE contract_fees;
TRUNCATE TABLE contract_subscriptions;
TRUNCATE TABLE contrat_assurance;
TRUNCATE TABLE credit_applications;
TRUNCATE TABLE demande_devis;
TRUNCATE TABLE demande_devis_credit_association;
TRUNCATE TABLE documents;
TRUNCATE TABLE kyc_document;
TRUNCATE TABLE kyc_submission;
TRUNCATE TABLE loan_payments;
TRUNCATE TABLE loans;
TRUNCATE TABLE messenger_messages;
TRUNCATE TABLE notifications;
TRUNCATE TABLE reset_password_request;
TRUNCATE TABLE sub_account_card;
TRUNCATE TABLE sub_account_credit;
TRUNCATE TABLE sub_account_insurance;
TRUNCATE TABLE sub_account_savings;
TRUNCATE TABLE transactions;
TRUNCATE TABLE transfer_attempts;
TRUNCATE TABLE transfer_codes;
TRUNCATE TABLE transfers;
TRUNCATE TABLE user;

-- Réactiver les contraintes de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- Vérification : afficher le nombre de lignes dans company_settings (devrait être préservé)
SELECT COUNT(*) as company_settings_count FROM company_settings;