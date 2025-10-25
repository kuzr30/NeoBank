<?php

namespace App\Controller\Admin;

use App\Entity\DemandeDevisCreditAssociation;
use App\Service\DemandeDevisCreditAssociationService;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Doctrine\ORM\EntityManagerInterface;

class DemandeDevisCreditAssociationCrudController extends AbstractCrudController
{
    public function __construct(
        private DemandeDevisCreditAssociationService $associationService
    ) {}

    public static function getEntityFqcn(): string
    {
        return DemandeDevisCreditAssociation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Association Devis-Crédit')
            ->setEntityLabelInPlural('Associations Devis-Crédit')
            ->setSearchFields(['demandeDevis.numeroDevis', 'creditApplication.referenceNumber'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $deactivateAction = Action::new('deactivate', 'Désactiver', 'fa fa-ban')
            ->linkToCrudAction('deactivate')
            ->displayIf(function (DemandeDevisCreditAssociation $entity) {
                return $entity->isActive();
            })
            ->setCssClass('btn btn-warning');

        $activateAction = Action::new('activate', 'Activer', 'fa fa-check')
            ->linkToCrudAction('activate')
            ->displayIf(function (DemandeDevisCreditAssociation $entity) {
                return !$entity->isActive();
            })
            ->setCssClass('btn btn-success');

        return $actions
            ->add(Crud::PAGE_INDEX, $deactivateAction)
            ->add(Crud::PAGE_INDEX, $activateAction)
            ->add(Crud::PAGE_DETAIL, $deactivateAction)
            ->add(Crud::PAGE_DETAIL, $activateAction);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm()->hideOnIndex(),
            AssociationField::new('demandeDevis', 'Demande de devis')
                ->formatValue(function ($value) {
                    if ($value instanceof \App\Entity\DemandeDevis) {
                        return sprintf('%s - %s %s', 
                            $value->getNumeroDevis(), 
                            $value->getPrenom(), 
                            $value->getNom()
                        );
                    }
                    return $value;
                }),
            AssociationField::new('creditApplication', 'Demande de crédit')
                ->formatValue(function ($value) {
                    if ($value instanceof \App\Entity\CreditApplication) {
                        return sprintf('%s - %s € (%d mois)', 
                            $value->getReferenceNumber(),
                            number_format($value->getLoanAmount(), 2, ',', ' '),
                            $value->getDuration()
                        );
                    }
                    return $value;
                }),
            BooleanField::new('isActive', 'Actif')
                ->renderAsSwitch(false),
            TextareaField::new('notes', 'Notes')
                ->hideOnIndex(),
            DateTimeField::new('createdAt', 'Date de création')
                ->setDisabled()
                ->hideOnForm(),
        ];
    }

    public function deactivate(AdminContext $context)
    {
        /** @var DemandeDevisCreditAssociation $association */
        $association = $context->getEntity()->getInstance();
        
        try {
            $this->associationService->removeAssociation($association);
            $this->addFlash('success', 'Association désactivée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la désactivation : ' . $e->getMessage());
        }
        
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
            
        return $this->redirect($url);
    }

    public function activate(AdminContext $context)
    {
        /** @var DemandeDevisCreditAssociation $association */
        $association = $context->getEntity()->getInstance();
        
        try {
            $association->setIsActive(true);
            $this->container->get('doctrine')->getManagerForClass(DemandeDevisCreditAssociation::class)->flush();
            $this->addFlash('success', 'Association activée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'activation : ' . $e->getMessage());
        }
        
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
            
        return $this->redirect($url);
    }

    public function createEntity(string $entityFqcn)
    {
        $association = new DemandeDevisCreditAssociation();
        $association->setNotes('Association manuelle');
        return $association;
    }
}