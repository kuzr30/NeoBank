<?php

namespace App\Controller\Admin;

use App\Entity\PaymentAccount;
use App\Repository\PaymentAccountRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PaymentAccountCrudController extends AbstractCrudController
{
    public function __construct(
        private PaymentAccountRepository $paymentAccountRepository,
        private AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return PaymentAccount::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Compte de paiement')
            ->setEntityLabelInPlural('Compte de paiement')
            ->setPageTitle(Crud::PAGE_INDEX, 'Compte de paiement')
            ->setPageTitle(Crud::PAGE_NEW, 'Configurer le compte de paiement')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier le compte de paiement')
            ->setHelp(Crud::PAGE_INDEX, 'Configurez le compte bancaire qui sera utilisé pour recevoir les paiements.')
            ->setPaginatorPageSize(1);
    }

    public function configureActions(Actions $actions): Actions
    {
        $existingAccount = $this->paymentAccountRepository->getPaymentAccount();
        
        $actions = $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE);
        
        // Si un compte existe déjà, remplacer "Nouveau" par un lien vers l'édition
        if ($existingAccount) {
            $actions = $actions->remove(Crud::PAGE_INDEX, Action::NEW);
            
            // Utiliser linkToUrl pour créer le lien de modification
            $editUrl = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::EDIT)
                ->setEntityId($existingAccount->getId())
                ->generateUrl();
            
            $editAction = Action::new('editAccount', 'Modifier', 'fa fa-edit')
                ->linkToUrl($editUrl);
            
            $actions = $actions->add(Crud::PAGE_INDEX, $editAction);
        } else {
            $actions = $actions->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('Configurer');
            });
        }
        
        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        
        yield TextField::new('accountName', 'Intitulé du compte')
            ->setRequired(true)
            ->setHelp('Exemple : SEDEF BANK - Compte principal');

        yield TextField::new('iban', 'IBAN')
            ->setRequired(true)
            ->setHelp('Exemple : FR76 1234 5678 9012 3456 7890 123');

        yield TextField::new('bic', 'BIC/SWIFT')
            ->setRequired(false)
            ->setHelp('Exemple : BNPAFRPP (optionnel)');

        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm')->hideOnForm()->hideOnIndex();

        yield DateTimeField::new('updatedAt', 'Modifié le')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm')->hideOnForm()->hideOnIndex();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof PaymentAccount) {
            // Vérifier s'il existe déjà un compte
            $existingAccount = $this->paymentAccountRepository->getPaymentAccount();
            
            if ($existingAccount) {
                // Mettre à jour le compte existant au lieu d'en créer un nouveau
                $existingAccount->setAccountName($entityInstance->getAccountName());
                $existingAccount->setIban($entityInstance->getIban());
                $existingAccount->setBic($entityInstance->getBic());
                $existingAccount->setUpdatedAtValue();
                
                $entityManager->flush();
                
                $this->addFlash('success', 'Le compte de paiement a été mis à jour avec succès.');
                return;
            }
        }
        
        parent::persistEntity($entityManager, $entityInstance);
        $this->addFlash('success', 'Le compte de paiement a été configuré avec succès.');
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::updateEntity($entityManager, $entityInstance);
        $this->addFlash('success', 'Le compte de paiement a été modifié avec succès.');
    }

    public function createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters): \Doctrine\ORM\QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        
        // Limiter à un seul résultat
        $qb->setMaxResults(1);
        
        return $qb;
    }
}
