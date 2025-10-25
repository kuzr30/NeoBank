<?php

namespace App\Controller\Admin;

use App\Entity\Loan;
use App\Enum\CreditTypeEnum;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;

class LoanCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Loan::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('loanNumber', 'Numéro de prêt')->hideOnForm(),
            AssociationField::new('account', 'Compte'),
            AssociationField::new('creditApplication', 'Demande de crédit')->hideOnIndex(),
            MoneyField::new('amount', 'Montant du prêt')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            MoneyField::new('remainingAmount', 'Montant restant')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            MoneyField::new('monthlyPayment', 'Mensualité')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            PercentField::new('interestRate', 'Taux d\'intérêt')->setNumDecimals(4),
            NumberField::new('termMonths', 'Durée (mois)'),
            NumberField::new('remainingMonths', 'Mois restants')->hideOnForm(),
            ChoiceField::new('type', 'Type de crédit')
                ->setChoices([
                    'Immobilier' => CreditTypeEnum::IMMOBILIER,
                    'Auto' => CreditTypeEnum::AUTO,
                    'Consommation' => CreditTypeEnum::CONSOMMATION,
                    'Travaux' => CreditTypeEnum::TRAVAUX,
                    'Professionnel' => CreditTypeEnum::PROFESSIONNEL,
                    'Personnel' => CreditTypeEnum::PERSONAL,
                    'Amélioration habitat' => CreditTypeEnum::HOME_IMPROVEMENT,
                    'Consolidation dettes' => CreditTypeEnum::DEBT_CONSOLIDATION,
                    'Hypothèque' => CreditTypeEnum::MORTGAGE,
                    'Renouvelable' => CreditTypeEnum::RENOUVELABLE,
                    'Étudiant' => CreditTypeEnum::ETUDIANT,
                    'Relais' => CreditTypeEnum::RELAIS,
                    'Microcrédit' => CreditTypeEnum::MICROCREDIT,
                    'Leasing' => CreditTypeEnum::LEASING,
                    'Voyage' => CreditTypeEnum::VOYAGE,
                ]),
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'En attente' => 'pending',
                    'Actif' => 'active',
                    'Terminé' => 'completed',
                    'Défaillant' => 'defaulted',
                    'Annulé' => 'cancelled',
                ]),
            TextareaField::new('purpose', 'Objet du prêt')->hideOnIndex(),
            DateTimeField::new('approvedAt', 'Approuvé le')->hideOnForm(),
            DateTimeField::new('disbursedAt', 'Débloqué le')->hideOnForm(),
            DateTimeField::new('firstPaymentDate', 'Première échéance')->hideOnIndex(),
            DateTimeField::new('nextPaymentDate', 'Prochaine échéance'),
            DateTimeField::new('lastPaymentDate', 'Dernière échéance')->hideOnIndex(),
            DateTimeField::new('completedAt', 'Terminé le')->hideOnForm(),
            DateTimeField::new('createdAt', 'Créé le')->hideOnForm(),
            DateTimeField::new('updatedAt', 'Modifié le')->hideOnForm(),
            MoneyField::new('totalInterest', 'Total intérêts')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->hideOnForm(),
            MoneyField::new('totalPaid', 'Total payé')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->hideOnForm(),
            NumberField::new('missedPayments', 'Échéances manquées')->hideOnForm(),
            ArrayField::new('terms', 'Conditions')->hideOnIndex(),
            CollectionField::new('payments', 'Échéances')->hideOnForm(),
        ];
    }
}
