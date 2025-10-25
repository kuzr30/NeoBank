<?php

namespace App\Controller\Admin;

use App\Entity\ContractFee;
use App\Entity\CreditApplication;
use App\Enum\CreditApplicationStatusEnum;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ContractFeeCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AdminUrlGenerator $adminUrlGenerator
    ) {
    }
    public static function getEntityFqcn(): string
    {
        // On va afficher les CreditApplication approuvées, pas les ContractFee
        return CreditApplication::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        // Afficher seulement les crédits approuvés
        return $this->entityManager->getRepository(CreditApplication::class)
            ->createQueryBuilder('ca')
            ->where('ca.status = :approved')
            ->setParameter('approved', CreditApplicationStatusEnum::APPROVED)
            ->orderBy('ca.id', 'DESC');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Crédit approuvé')
            ->setEntityLabelInPlural('Crédits approuvés - Gestion des frais')
            ->setPageTitle('index', 'Crédits approuvés - Ajouter des frais')
            ->setSearchFields(['firstName', 'lastName', 'email', 'referenceNumber'])
            ->setDefaultSort(['id' => 'DESC'])
            ->showEntityActionsInlined(); // Afficher les actions dans chaque ligne
    }

    public function configureActions(Actions $actions): Actions
    {
        $addFeesAction = Action::new('addContractFees', 'Ajouter frais', 'fa fa-calculator')
            ->linkToCrudAction('addContractFees')
            ->setCssClass('btn btn-primary');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $addFeesAction)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::NEW) // Supprimer complètement l'action NEW
            ->setPermission(Action::DETAIL, 'ROLE_ADMIN');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', '#')->hideOnForm()->hideOnIndex(),
            TextField::new('referenceNumber', 'Référence'),
            TextField::new('firstName', 'Prénom'),
            TextField::new('lastName', 'Nom'),
            EmailField::new('email', 'Email'),
            MoneyField::new('loanAmount', 'Montant')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            ChoiceField::new('creditType', 'Type')
                ->setChoices(\App\Enum\CreditTypeEnum::getFrenchChoices())
                ->formatValue(function ($value, $entity) {
                    return $value ? $value->getFrenchLabel() : '';
                })->hideOnForm()->hideOnIndex(),

            // TextField::new('fraisExistants', 'Frais existants')
            //     ->formatValue(function ($value, $entity) {
            //         /** @var CreditApplication $entity */
            //         $fees = $entity->getContractFees();
            //         if ($fees->count() === 0) {
            //             return '<span class="badge bg-warning">Aucun frais</span>';
            //         }
            //         $total = 0;
            //         foreach ($fees as $fee) {
            //             $total += (float)$fee->getAmount();
            //         }
            //         return sprintf('<span class="badge bg-info">%d frais - Total: %.2f €</span>', 
            //             $fees->count(), $total);
            //     })
            //     ->hideOnForm()
            //     ->onlyOnIndex(),
                
            DateTimeField::new('createdAt', 'Date de création')
                ->hideOnForm()->hideOnIndex()
                ->setFormat('dd/MM/yyyy HH:mm'),
        ];
    }

    public function addContractFees(AdminContext $context): RedirectResponse
    {
        /** @var CreditApplication $creditApplication */
        $creditApplication = $context->getEntity()->getInstance();
        
        // Créer une URL complètement propre sans hériter des paramètres existants
        $url = $this->adminUrlGenerator
            ->unsetAll() // Supprimer TOUS les paramètres existants
            ->setController(ContractFeeFormCrudController::class)
            ->setAction(Action::NEW)
            ->set('creditApplicationId', $creditApplication->getId())
            ->generateUrl();

        return $this->redirect($url);
    }
}
