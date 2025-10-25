<?php

namespace App\Controller\Admin;

use App\Entity\ContractFee;
use App\Entity\CreditApplication;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ContractFeeFormCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AdminUrlGenerator $adminUrlGenerator
    ) {}

    public static function getEntityFqcn(): string
    {
        return ContractFee::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Frais de contrat')
            ->setEntityLabelInSingular('Frais de contrat')
            ->setPageTitle('new', 'Ajouter un frais de contrat')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, function (Action $action) {
                return $action->setLabel('Créer le frais');
            })
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('creditApplication', 'Demande de crédit')
                ->formatValue(function ($value, $entity) {
                    if (!$value) return 'Aucune';
                    return sprintf('%s - %s %s (%.2f €)', 
                        $value->getReferenceNumber(),
                        $value->getFirstName(),
                        $value->getLastName(),
                        (float)$value->getLoanAmount()
                    );
                })
                ->setRequired(true),
            TextField::new('name', 'Nom du frais')
                ->setHelp('Ex: Frais de dossier, Frais de garantie, etc.')
                ->setRequired(true),
            MoneyField::new('amount', 'Montant')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->setHelp('Montant en euros')
                ->setRequired(true)
        ];
    }

    public function createEntity(string $entityFqcn)
    {
        $contractFee = new ContractFee();
        
        // Si un creditApplicationId est passé en paramètre, on pré-remplit la demande de crédit
        $creditApplicationId = $this->getContext()->getRequest()->query->get('creditApplicationId');
        
        if ($creditApplicationId) {
            $creditApplication = $this->entityManager->getRepository(CreditApplication::class)
                ->find($creditApplicationId);
            
            if ($creditApplication && $creditApplication->getStatus() === \App\Enum\CreditApplicationStatusEnum::APPROVED) {
                $contractFee->setCreditApplication($creditApplication);
            }
        }
        
        return $contractFee;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var ContractFee $contractFee */
        $contractFee = $entityInstance;
        
        // Vérifier que le frais est bien lié à une demande de crédit
        if (!$contractFee->getCreditApplication()) {
            $this->addFlash('danger', 'Le frais doit être lié à une demande de crédit valide.');
            return;
        }
        
        // Vérifier que la demande de crédit est bien approuvée
        if ($contractFee->getCreditApplication()->getStatus() !== \App\Enum\CreditApplicationStatusEnum::APPROVED) {
            $this->addFlash('danger', 'La demande de crédit doit être approuvée pour ajouter des frais.');
            return;
        }
        
        parent::persistEntity($entityManager, $entityInstance);
        
        $this->addFlash('success', 'Le frais de contrat a été créé avec succès.');
    }
}
