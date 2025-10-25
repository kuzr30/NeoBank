<?php

namespace App\Controller\Admin;

use App\Entity\Account;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;

class AccountCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Account::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm()->hideOnIndex(),
            TextField::new('accountNumber', 'Numéro de compte')->hideOnForm()->hideOnIndex(),
            TextField::new('iban', 'IBAN')->hideOnForm()->hideOnIndex(),
            AssociationField::new('owner', 'Propriétaire'),
            MoneyField::new('balance', 'Solde')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)->hideOnIndex(),
            MoneyField::new('balance', 'Solde')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)->hideOnForm(),
            ChoiceField::new('type', 'Type de compte')
                ->setChoices([
                    'Compte courant' => 'checking',
                    'Compte épargne' => 'savings',
                    'Compte professionnel' => 'business',
                    'Compte joint' => 'joint',
                ])->hideOnIndex(),
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'Actif' => 'active',
                    'Suspendu' => 'suspended',
                    'Fermé' => 'closed',
                    'Gelé' => 'frozen',
                ]),
            TextField::new('currency', 'Devise')->setHelp('Code ISO à 3 lettres (EUR, USD, etc.)')->hideOnForm()->hideOnIndex(),
            MoneyField::new('overdraftLimit', 'Limite de découvert')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            NumberField::new('interestRate', 'Taux d\'intérêt')->setNumDecimals(4)->hideOnIndex(),
            DateTimeField::new('createdAt', 'Créé le')->hideOnForm()->hideOnIndex(),
            DateTimeField::new('updatedAt', 'Modifié le')->hideOnForm()->hideOnIndex(),
            DateTimeField::new('closedAt', 'Fermé le')->hideOnForm()->hideOnIndex(),
            CollectionField::new('transactions', 'Transactions')->hideOnForm()->hideOnIndex(),
            CollectionField::new('cards', 'Cartes')->hideOnForm()->hideOnIndex(),
            CollectionField::new('loans', 'Prêts')->hideOnForm()->hideOnIndex(),
        ];
    }
}
