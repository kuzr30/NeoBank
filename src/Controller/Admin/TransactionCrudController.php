<?php

namespace App\Controller\Admin;

use App\Entity\Transaction;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;

class TransactionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Transaction::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('reference', 'Référence')->hideOnForm(),
            AssociationField::new('sourceAccount', 'Compte source'),
            AssociationField::new('destinationAccount', 'Compte destination')->hideOnIndex(),
            AssociationField::new('initiatedBy', 'Initié par')->hideOnIndex(),
            MoneyField::new('amount', 'Montant')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            ChoiceField::new('type', 'Type')
                ->setChoices([
                    'Débit' => 'debit',
                    'Crédit' => 'credit',
                    'Virement' => 'transfer',
                    'Prélèvement' => 'direct_debit',
                    'Chèque' => 'check',
                    'Carte' => 'card',
                    'Espèces' => 'cash',
                    'Virement SEPA' => 'sepa_transfer',
                    'Paiement' => 'payment',
                    'Remboursement' => 'refund',
                ]),
            ChoiceField::new('category', 'Catégorie')
                ->setChoices([
                    'Salaire' => 'salary',
                    'Courses' => 'groceries',
                    'Transport' => 'transport',
                    'Restaurants' => 'dining',
                    'Divertissement' => 'entertainment',
                    'Santé' => 'health',
                    'Shopping' => 'shopping',
                    'Factures' => 'bills',
                    'Éducation' => 'education',
                    'Voyage' => 'travel',
                    'Investissement' => 'investment',
                    'Épargne' => 'savings',
                    'Assurance' => 'insurance',
                    'Impôts' => 'taxes',
                    'Immobilier' => 'real_estate',
                    'Autre' => 'other',
                ]),
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'En attente' => 'pending',
                    'En cours' => 'processing',
                    'Terminé' => 'completed',
                    'Échoué' => 'failed',
                    'Annulé' => 'cancelled',
                ]),
            TextField::new('currency', 'Devise'),
            TextareaField::new('description', 'Description')->hideOnIndex(),
            TextareaField::new('notes', 'Notes')->hideOnIndex(),
            MoneyField::new('balanceAfter', 'Solde après')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->hideOnIndex(),
            DateTimeField::new('processedAt', 'Traité le')->hideOnForm(),
            DateTimeField::new('createdAt', 'Créé le')->hideOnForm(),
            DateTimeField::new('updatedAt', 'Modifié le')->hideOnForm(),
            ArrayField::new('metadata', 'Métadonnées')->hideOnIndex(),
        ];
    }
}
