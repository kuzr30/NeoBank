<?php

namespace App\Controller\Admin;

use App\Entity\TransferAttempt;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

class TransferAttemptCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TransferAttempt::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Tentative de validation')
            ->setEntityLabelInPlural('Tentatives de validation')
            ->setDefaultSort(['attemptedAt' => 'DESC'])
            ->showEntityActionsInlined()
            ->setSearchFields(['inputCode', 'transfer.user.email', 'transferCode.codeName']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // Les tentatives sont créées automatiquement lors des validations
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnDetail(),
            
            AssociationField::new('transfer', 'Virement')
                ->setColumns(4)
                ->formatValue(function ($value, $entity) {
                    if (!$value) return 'N/A';
                    $user = $value->getUser();
                    $userInfo = $user ? $user->getFirstName() . ' ' . $user->getLastName() . ' (' . $user->getEmail() . ')' : 'N/A';
                    return sprintf(
                        'Virement #%d (%s €)<br><small>%s</small>',
                        $value->getId(),
                        $value->getAmount(),
                        $userInfo
                    );
                }),
            
            AssociationField::new('transferCode', 'Code de sécurité')
                ->setColumns(3)
                ->formatValue(function ($value, $entity) {
                    if (!$value) return 'N/A';
                    return sprintf(
                        '%s (Ordre: %d)',
                        $value->getCodeName(),
                        $value->getCodeOrder()
                    );
                }),
            
            TextField::new('inputCode', 'Code saisi')
                ->setColumns(3)
                ->formatValue(function ($value, $entity) {
                    // Afficher le code complet pour l'administration
                    return $value ?: 'N/A';
                }),
            
            // Informations du client
            TextField::new('transfer.user.firstName', 'Prénom')
                ->setColumns(2)
                ->onlyOnIndex(),
            
            TextField::new('transfer.user.lastName', 'Nom')
                ->setColumns(2)
                ->onlyOnIndex(),
            
            TextField::new('transfer.user.email', 'Email')
                ->setColumns(3)
                ->onlyOnIndex(),
            
            BooleanField::new('isSuccess', 'Succès')
                ->setColumns(2),
            
            DateTimeField::new('attemptedAt', 'Tenté le')
                ->setColumns(4),
            
            // Détails supplémentaires en vue détail
            TextField::new('transfer.user.email', 'Email utilisateur')
                ->onlyOnDetail(),
            
            TextField::new('transfer.user.firstName', 'Prénom utilisateur')
                ->onlyOnDetail(),
            
            TextField::new('transfer.user.lastName', 'Nom utilisateur')
                ->onlyOnDetail(),
            
            TextField::new('transferCode.codeValue', 'Code attendu')
                ->onlyOnDetail()
                ->formatValue(function ($value, $entity) {
                    // Afficher le code complet pour l'administration en vue détail
                    return $value ?: 'N/A';
                }),
            
            TextField::new('transfer.status', 'Statut du virement')
                ->onlyOnDetail(),
            
            TextField::new('transferCode.status', 'Statut du code')
                ->onlyOnDetail(),
        ];
    }
}
