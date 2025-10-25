<?php

namespace App\Controller\Admin;

use App\Entity\Card;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class CardCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Card::class;
    }

        public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Gestion de carte')
            ->setEntityLabelInPlural('Gestion des cartes')
            ->setPageTitle('index', 'Gestion des cartes bancaires')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(20);
    }

    public function configureFields(string $pageName): iterable
    {
        return [

            IdField::new('id')->hideOnForm()->hideOnIndex(),
            TextField::new('cardNumber', 'Numéro de carte')->hideOnForm(),
            AssociationField::new('account', 'Compte'),
            TextField::new('cardholderName', 'Nom du porteur'),
            DateField::new('expiryDate', 'Date d\'expiration'),
            TextField::new('cvv', 'CVV')->hideOnIndex(),
            ChoiceField::new('type', 'Type')
                ->setChoices([
                    'Débit' => 'debit',
                    'Crédit' => 'credit',
                    'Prépayée' => 'prepaid',
                ]),
            ChoiceField::new('category', 'Catégorie')
                ->setChoices([
                    'Classic' => 'classic',
                    'Gold' => 'gold',
                    'Platinum' => 'platinum',
                    'Black' => 'black',
                ]),
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'Active' => 'active',
                    'Bloquée' => 'blocked',
                    'Expirée' => 'expired',
                    'Annulée' => 'cancelled',
                ]),
            MoneyField::new('dailyLimit', 'Limite journalière')->setCurrency('EUR')->hideOnIndex(),
            MoneyField::new('monthlyLimit', 'Limite mensuelle')->setCurrency('EUR')->hideOnIndex(),
            MoneyField::new('creditLimit', 'Limite de crédit')->setCurrency('EUR')->hideOnIndex(),
            MoneyField::new('dailySpent', 'Dépenses journalières')->setCurrency('EUR')->hideOnForm()->hideOnIndex(),
            MoneyField::new('monthlySpent', 'Dépenses mensuelles')->setCurrency('EUR')->hideOnForm()->hideOnIndex(),
            BooleanField::new('contactless', 'Sans contact')->hideOnForm()->hideOnIndex(),
            BooleanField::new('onlinePayments', 'Paiements en ligne')->hideOnForm()->hideOnIndex(),
            BooleanField::new('internationalPayments', 'Paiements internationaux')->hideOnForm()->hideOnIndex(),
            NumberField::new('pinAttempts', 'Tentatives PIN')->hideOnForm()->hideOnIndex(),
            DateTimeField::new('lastUsedAt', 'Dernière utilisation')->hideOnForm()->hideOnIndex(),
            DateTimeField::new('createdAt', 'Créée le')->hideOnForm()->hideOnIndex(),
            DateTimeField::new('updatedAt', 'Modifiée le')->hideOnForm()->hideOnIndex(),
            DateTimeField::new('blockedAt', 'Bloquée le')->hideOnForm(),
            ArrayField::new('settings', 'Paramètres')->hideOnIndex(),
        ];
    }
}
