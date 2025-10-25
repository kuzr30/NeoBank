<?php

namespace App\Controller\Admin;

use App\Entity\LoanPayment;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;

class LoanPaymentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LoanPayment::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('paymentNumber', 'Numéro de paiement')->hideOnForm(),
            AssociationField::new('loan', 'Prêt'),
            AssociationField::new('transaction', 'Transaction')->hideOnIndex(),
            MoneyField::new('amount', 'Montant total')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            MoneyField::new('principalAmount', 'Capital')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            MoneyField::new('interestAmount', 'Intérêts')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            MoneyField::new('feeAmount', 'Frais')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->hideOnIndex(),
            MoneyField::new('lateFee', 'Pénalités de retard')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->hideOnIndex(),
            ChoiceField::new('type', 'Type')
                ->setChoices([
                    'Régulier' => 'regular',
                    'Anticipé' => 'early',
                    'Partiel' => 'partial',
                    'Final' => 'final',
                ]),
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'En attente' => 'pending',
                    'Payé' => 'paid',
                    'En retard' => 'overdue',
                    'Annulé' => 'cancelled',
                ]),
            DateField::new('dueDate', 'Date d\'échéance'),
            DateTimeField::new('paidAt', 'Payé le')->hideOnForm(),
            DateTimeField::new('scheduledAt', 'Programmé le')->hideOnIndex(),
            DateTimeField::new('createdAt', 'Créé le')->hideOnForm(),
            DateTimeField::new('updatedAt', 'Modifié le')->hideOnForm(),
            TextareaField::new('notes', 'Notes')->hideOnIndex(),
            ArrayField::new('metadata', 'Métadonnées')->hideOnIndex(),
        ];
    }
}
