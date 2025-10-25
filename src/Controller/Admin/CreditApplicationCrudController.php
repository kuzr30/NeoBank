<?php

namespace App\Controller\Admin;

use App\Entity\CreditApplication;
use App\Enum\CreditApplicationStatusEnum;
use App\Service\CreditApplicationService;
use App\Service\CreditWorkflowService;
use App\Controller\Admin\ContractFeeCrudController;
use App\Controller\Admin\ContractFeeFormCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use App\Enum\CreditTypeEnum;
use App\Enum\DurationUnitEnum;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Doctrine\ORM\EntityManagerInterface;

class CreditApplicationCrudController extends AbstractCrudController
{
    public function __construct(
        private CreditApplicationService $creditApplicationService,
        private EntityManagerInterface $entityManager,
        private AdminUrlGenerator $adminUrlGenerator,
        private CreditWorkflowService $creditWorkflowService
    ) {}

    public static function getEntityFqcn(): string
    {
        return CreditApplication::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Demande de crédit')
            ->setEntityLabelInPlural('Demandes de crédit')
            ->setPageTitle('index', 'Gestion des demandes de crédit')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['firstName', 'lastName', 'email', 'companyName'])
            ->setPaginatorPageSize(20);
    }

    public function configureActions(Actions $actions): Actions
    {
        // Actions personnalisées
        $approveAction = Action::new('approve', 'Approuver', 'fa fa-check')
            ->linkToCrudAction('approveApplication')
            ->setCssClass('btn btn-success')
            ->displayIf(static function (CreditApplication $entity) {
                return in_array($entity->getStatus(), [
                    CreditApplicationStatusEnum::PENDING,
                    CreditApplicationStatusEnum::IN_PROGRESS,
                    CreditApplicationStatusEnum::IN_REVIEW,
                    CreditApplicationStatusEnum::REQUIRES_DOCUMENTS
                ]);
            });

        $rejectAction = Action::new('reject', 'Rejeter', 'fa fa-times')
            ->linkToCrudAction('rejectApplication')
            ->setCssClass('btn btn-danger')
            ->displayIf(static function (CreditApplication $entity) {
                return in_array($entity->getStatus(), [
                    CreditApplicationStatusEnum::PENDING,
                    CreditApplicationStatusEnum::IN_PROGRESS,
                    CreditApplicationStatusEnum::IN_REVIEW,
                    CreditApplicationStatusEnum::REQUIRES_DOCUMENTS,
                    CreditApplicationStatusEnum::APPROVED
                ]);
            });

        $reviewAction = Action::new('review', 'Marquer en cours', 'fa fa-eye')
            ->linkToCrudAction('markInReview')
            ->setCssClass('btn btn-warning')
            ->displayIf(static function (CreditApplication $entity) {
                return $entity->getStatus() === CreditApplicationStatusEnum::PENDING;
            });

        $markInProgressAction = Action::new('markInProgress', 'En cours de traitement', 'fa fa-play')
            ->linkToCrudAction('markInProgress')
            ->setCssClass('btn btn-info')
            ->displayIf(static function (CreditApplication $entity) {
                return in_array($entity->getStatus(), [
                    CreditApplicationStatusEnum::PENDING,
                    CreditApplicationStatusEnum::IN_REVIEW
                ]);
            });

        $addFeesAction = Action::new('addFees', 'Ajouter frais', 'fa fa-calculator')
            ->linkToCrudAction('addContractFees')
            ->setCssClass('btn btn-primary')
            ->displayIf(static function (CreditApplication $entity) {
                return $entity->getStatus() === CreditApplicationStatusEnum::APPROVED;
            });

        $sendContractAction = Action::new('sendContract', 'Envoyer contrat', 'fa fa-file-contract')
            ->linkToCrudAction('sendContract')
            ->setCssClass('btn btn-success')
            ->displayIf(static function (CreditApplication $entity) {
                // Afficher seulement si approuvé ET qu'il y a des frais
                return $entity->getStatus() === CreditApplicationStatusEnum::APPROVED 
                    && $entity->getContractFees()->count() > 0;
            });

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $approveAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            ->add(Crud::PAGE_INDEX, $reviewAction)
            ->add(Crud::PAGE_INDEX, $markInProgressAction)
            ->add(Crud::PAGE_INDEX, $addFeesAction)
            ->add(Crud::PAGE_INDEX, $sendContractAction)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction)
            ->add(Crud::PAGE_DETAIL, $reviewAction)
            ->add(Crud::PAGE_DETAIL, $markInProgressAction)
            ->add(Crud::PAGE_DETAIL, $addFeesAction)
            ->add(Crud::PAGE_DETAIL, $sendContractAction)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setIcon('fa fa-plus')->setLabel('Nouvelle demande');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-edit');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash')->displayIf(static function (CreditApplication $entity) {
                    return !$entity->getStatus()->isActive();
                });
            });
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices(CreditApplicationStatusEnum::getFrenchChoices()))
            ->add(ChoiceFilter::new('creditType', 'Type de crédit')->setChoices(CreditTypeEnum::getFrenchChoices()))
            ->add(NumericFilter::new('loanAmount', 'Montant'))
            ->add(NumericFilter::new('duration', 'Durée (mois)'))
            ->add(DateTimeFilter::new('createdAt', 'Date de création'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();

        // Informations personnelles
        yield TextField::new('firstName', 'Prénom')
            ->setRequired(true);
            
        yield TextField::new('lastName', 'Nom')
            ->setRequired(true);
            
        yield EmailField::new('email', 'Email')
            ->setRequired(true);

        yield TextField::new('phone', 'Téléphone')->hideOnIndex();
      

        // Statut - avec traductions françaises ET couleurs
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(CreditApplicationStatusEnum::getFrenchChoices())
            ->formatValue(function ($value, $entity) {
                if (!$value) return '';
                $label = $value->getFrenchLabel();
                $badgeClass = match($value) {
                    CreditApplicationStatusEnum::PENDING => 'secondary',
                    CreditApplicationStatusEnum::IN_PROGRESS => 'info',
                    CreditApplicationStatusEnum::IN_REVIEW => 'warning',
                    CreditApplicationStatusEnum::REQUIRES_DOCUMENTS => 'info',
                    CreditApplicationStatusEnum::APPROVED => 'success',
                    CreditApplicationStatusEnum::CONTRACT_SENT => 'primary',
                    CreditApplicationStatusEnum::CONTRACT_SIGNED => 'secondary',
                    CreditApplicationStatusEnum::CONTRACT_VALIDATED => 'success',
                    CreditApplicationStatusEnum::FUNDS_DISBURSED => 'success',
                    CreditApplicationStatusEnum::REJECTED => 'danger',
                    CreditApplicationStatusEnum::CANCELLED => 'dark',
                };
                return sprintf('<span class="badge bg-%s">%s</span>', $badgeClass, $label);
            })
            ->hideOnForm();

        // Informations du crédit
        yield MoneyField::new('loanAmount', 'Montant')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setRequired(true);

        // Type de crédit - avec traductions françaises ET couleurs
        yield ChoiceField::new('creditType', 'Type de crédit')
            ->setChoices(CreditTypeEnum::getFrenchChoices())
            ->formatValue(function ($value, $entity) {
                if (!$value) return '';
                $label = $value->getFrenchLabel();
                $badgeClass = match($value) {
                    CreditTypeEnum::IMMOBILIER => 'info',
                    CreditTypeEnum::AUTO => 'primary',
                    CreditTypeEnum::CONSOMMATION => 'success',
                    CreditTypeEnum::TRAVAUX => 'warning',
                    CreditTypeEnum::PROFESSIONNEL => 'dark',
                    CreditTypeEnum::PERSONAL => 'success',
                    CreditTypeEnum::HOME_IMPROVEMENT => 'warning',
                    CreditTypeEnum::DEBT_CONSOLIDATION => 'secondary',
                    CreditTypeEnum::MORTGAGE => 'info',
                    CreditTypeEnum::RENOUVELABLE => 'primary',
                    CreditTypeEnum::ETUDIANT => 'light',
                    CreditTypeEnum::RELAIS => 'info',
                    CreditTypeEnum::MICROCREDIT => 'success',
                    CreditTypeEnum::LEASING => 'primary',
                    CreditTypeEnum::VOYAGE => 'danger',
                };
                return sprintf('<span class="badge bg-%s">%s</span>', $badgeClass, $label);
            })
            ->setRequired(true);

        yield IntegerField::new('duration', 'Durée (mois)')
            ->setRequired(true);

        yield MoneyField::new('monthlyPayment', 'Mensualité')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->hideOnForm();

        // Informations professionnelles
        yield TextField::new('employer', 'Entreprise')
            ->hideOnIndex();

        yield TextField::new('jobTitle', 'Poste')
            ->hideOnIndex();

        yield MoneyField::new('monthlyIncome', 'Revenus mensuels')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->hideOnIndex();

        yield MoneyField::new('monthlyExpenses', 'Charges mensuelles')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->hideOnIndex();

        // Adresse
        yield TextField::new('address', 'Adresse')
            ->hideOnIndex();

        yield TextField::new('city', 'Ville')
            ->hideOnIndex();

        yield TextField::new('postalCode', 'Code postal')
            ->hideOnIndex();

        // Notes admin
        yield TextareaField::new('notes', 'Notes administratives')
            ->hideOnIndex()
            ->setHelp('Notes internes pour le suivi de la demande');

        // Consentements
        yield BooleanField::new('termsAccepted', 'Conditions acceptées')
            ->hideOnIndex();

        yield BooleanField::new('dataProcessingAccepted', 'Traitement données accepté')
            ->hideOnIndex();

        // Dates
/*         yield DateTimeField::new('createdAt', 'Date de création')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');

        yield DateTimeField::new('updatedAt', 'Dernière modification')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm'); */
    }

    // Actions personnalisées
    public function approveApplication(AdminContext $context): RedirectResponse
    {
        /** @var CreditApplication $creditApplication */
        $creditApplication = $context->getEntity()->getInstance();
        
        try {
            $this->creditWorkflowService->approveApplication(
                $creditApplication,
                'Demande approuvée par l\'administrateur le ' . date('d/m/Y H:i')
            );

            $this->addFlash('success', sprintf(
                'La demande de crédit #%d a été approuvée avec succès. Le client va recevoir un email de confirmation.',
                $creditApplication->getId()
            ));
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'approbation : ' . $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function rejectApplication(AdminContext $context): RedirectResponse
    {
        /** @var CreditApplication $creditApplication */
        $creditApplication = $context->getEntity()->getInstance();
        
        try {
            $this->creditWorkflowService->rejectApplication(
                $creditApplication,
                'Demande rejetée par l\'administrateur le ' . date('d/m/Y H:i')
            );

            $this->addFlash('success', sprintf(
                'La demande de crédit #%d a été rejetée. Le client va recevoir un email de notification.',
                $creditApplication->getId()
            ));
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du rejet : ' . $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function markInReview(AdminContext $context): RedirectResponse
    {
        /** @var CreditApplication $creditApplication */
        $creditApplication = $context->getEntity()->getInstance();
        
        $this->creditApplicationService->updateStatus(
            $creditApplication,
            CreditApplicationStatusEnum::IN_REVIEW,
            'Demande mise en cours d\'étude par l\'administrateur le ' . date('d/m/Y H:i')
        );

        $this->addFlash('info', sprintf(
            'La demande de crédit #%d est maintenant en cours d\'étude.',
            $creditApplication->getId()
        ));

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function markInProgress(AdminContext $context): RedirectResponse
    {
        /** @var CreditApplication $creditApplication */
        $creditApplication = $context->getEntity()->getInstance();
        
        $this->creditApplicationService->updateStatus(
            $creditApplication,
            CreditApplicationStatusEnum::IN_PROGRESS,
            'Demande mise en cours de traitement par l\'administrateur le ' . date('d/m/Y H:i')
        );

        $this->addFlash('info', sprintf(
            'La demande de crédit #%d est maintenant en cours de traitement.',
            $creditApplication->getId()
        ));

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function addContractFees(AdminContext $context): RedirectResponse
    {
        /** @var CreditApplication $creditApplication */
        $creditApplication = $context->getEntity()->getInstance();
        
        // Rediriger vers le formulaire de création de frais avec l'ID de la demande de crédit pré-rempli
        $url = $this->adminUrlGenerator
            ->unsetAll() // Supprimer TOUS les paramètres existants pour éviter les conflits
            ->setController(ContractFeeFormCrudController::class)
            ->setAction(Action::NEW)
            ->set('creditApplicationId', $creditApplication->getId())
            ->generateUrl();

        return $this->redirect($url);
    }

    public function sendContract(AdminContext $context): RedirectResponse
    {
        /** @var CreditApplication $creditApplication */
        $creditApplication = $context->getEntity()->getInstance();
        
        try {
            // Vérifier qu'il y a des frais
            if ($creditApplication->getContractFees()->count() === 0) {
                $this->addFlash('danger', 'Impossible d\'envoyer le contrat : aucun frais n\'a été ajouté. Veuillez d\'abord ajouter les frais de contrat.');
                
                $url = $this->adminUrlGenerator
                    ->setController(self::class)
                    ->setAction(Action::INDEX)
                    ->generateUrl();
                
                return $this->redirect($url);
            }

            // Générer et envoyer le contrat avec les frais inclus
            $this->creditWorkflowService->generateAndSendContract($creditApplication);

            $this->addFlash('success', sprintf(
                'Le contrat pour la demande de crédit #%d a été généré et envoyé avec succès. Le client va recevoir un email avec le contrat.',
                $creditApplication->getId()
            ));
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'envoi du contrat : ' . $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }
}
