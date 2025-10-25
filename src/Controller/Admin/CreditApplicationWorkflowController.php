<?php

namespace App\Controller\Admin;

use App\Entity\CreditApplication;
use App\Entity\ContractFee;
use App\Controller\Admin\ContractFeeCrudController;
use App\Service\CreditWorkflowService;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\CreditApplicationStatusEnum;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

class CreditApplicationWorkflowController extends AbstractCrudController
{
    public function __construct(
        private CreditWorkflowService $creditWorkflowService,
        private AdminUrlGenerator $adminUrlGenerator,
        private EntityManagerInterface $entityManager
    ) {}

    public static function getEntityFqcn(): string
    {
        return CreditApplication::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->leftJoin('entity.contractFees', 'contractFees')
           ->addSelect('contractFees');
        
        return $qb;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Demande de crédit')
            ->setEntityLabelInPlural('Gestion des demandes de crédit')
            ->setSearchFields(['email', 'firstName', 'lastName', 'adminNotes'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('firstName', 'Prénom'),
            TextField::new('lastName', 'Nom'),
            // EmailField::new('email', 'Email'),
            MoneyField::new('loanAmount', 'Montant demandé')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            IntegerField::new('duration', 'Durée en mois'),
           /*  ChoiceField::new('durationUnit', 'Unité')
                ->setChoices([
                    'Mois' => 'months',
                    'Années' => 'years'
                ]), */
            MoneyField::new('monthlyPayment', 'Mensualité')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->hideOnForm(),
     /*        MoneyField::new('totalCost', 'Coût total')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->hideOnForm(), */
            ChoiceField::new('status', 'Statut')
                ->setChoices(CreditApplicationStatusEnum::getChoices())
                ->renderAsBadges(CreditApplicationStatusEnum::getBadgeTypes()),
            BooleanField::new('contractPath', 'Contrat généré')
                ->hideOnForm()
                ->renderAsSwitch(false)
                ->setHelp('Indique si un contrat PDF a été généré'),
            TextField::new('feesStatusDisplay', 'Frais configurés')
                ->hideOnForm()
                ->setHelp('Indique le nombre de frais définis'),
            TextareaField::new('adminNotes', 'Notes admin')->hideOnIndex(),
            DateTimeField::new('createdAt', 'Créé le')->hideOnIndex(),
            DateTimeField::new('updatedAt', 'Modifié le')->hideOnIndex(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $approveAction = Action::new('approve', 'Approuver', 'fa fa-check')
            ->linkToCrudAction('approveApplication')
            ->addCssClass('btn btn-success')
            ->displayIf(static function (CreditApplication $entity) {
                return $entity->getStatus() === CreditApplicationStatusEnum::PENDING;
            });

        $rejectAction = Action::new('reject', 'Rejeter', 'fa fa-times')
            ->linkToCrudAction('rejectApplication')
            ->addCssClass('btn btn-danger')
            ->displayIf(static function (CreditApplication $entity) {
                return $entity->getStatus() === CreditApplicationStatusEnum::PENDING;
            });

        $generateContractAction = Action::new('generateContract', 'Générer contrat', 'fa fa-file-pdf')
            ->linkToCrudAction('generateContract')
            ->addCssClass('btn btn-info')
            ->displayIf(static function (CreditApplication $entity) {
                return $entity->getStatus() === CreditApplicationStatusEnum::APPROVED && $entity->hasAllRequiredFees();
            });

        $manageFees = Action::new('manageFees', 'Ajouter frais', 'fa fa-plus')
            ->linkToCrudAction('manageFees')
            ->addCssClass('btn btn-success')
            ->displayIf(static function (CreditApplication $entity) {
                return $entity->getStatus() === CreditApplicationStatusEnum::APPROVED;
            });

        $viewFees = Action::new('viewFees', 'Voir frais', 'fa fa-list')
            ->linkToCrudAction('viewFees')
            ->addCssClass('btn btn-info')
            ->displayIf(static function (CreditApplication $entity) {
                return $entity->getStatus() === CreditApplicationStatusEnum::APPROVED && $entity->getContractFees()->count() > 0;
            });

        $markAsSignedAction = Action::new('markAsSigned', 'Marquer comme signé', 'fa fa-signature')
            ->linkToCrudAction('markAsSigned')
            ->addCssClass('btn btn-primary')
            ->displayIf(static function (CreditApplication $entity) {
                return $entity->getStatus() === CreditApplicationStatusEnum::CONTRACT_SENT;
            });

        $validateContractAction = Action::new('validateContract', 'Valider contrat', 'fa fa-stamp')
            ->linkToCrudAction('validateContract')
            ->addCssClass('btn btn-warning')
            ->displayIf(static function (CreditApplication $entity) {
                return $entity->getStatus() === CreditApplicationStatusEnum::CONTRACT_SIGNED;
            });

        $disburseFundsAction = Action::new('disburseFunds', 'Débloquer les fonds', 'fa fa-money-check-alt')
            ->linkToCrudAction('disburseFunds')
            ->addCssClass('btn btn-success')
            ->displayIf(static function (CreditApplication $entity) {
                return $entity->getStatus() === CreditApplicationStatusEnum::CONTRACT_VALIDATED;
            });

        $downloadContractAction = Action::new('downloadContract', 'Télécharger contrat', 'fa fa-download')
            ->linkToCrudAction('downloadContract')
            ->addCssClass('btn btn-secondary')
            ->displayIf(static function (CreditApplication $entity) {
                return $entity->getContractPath() !== null;
            });

        $viewContractAction = Action::new('viewContract', 'Voir contrat', 'fa fa-eye')
            ->linkToCrudAction('viewContract')
            ->addCssClass('btn btn-outline-secondary')
            ->displayIf(static function (CreditApplication $entity) {
                return $entity->getContractPath() !== null;
            });

        $resendContractAction = Action::new('resendContract', 'Renvoyer contrat', 'fa fa-paper-plane')
            ->linkToCrudAction('resendContract')
            ->addCssClass('btn btn-success')
            ->displayIf(static function (CreditApplication $entity) {
                return $entity->getContractPath() !== null && 
                       in_array($entity->getStatus(), [
                           CreditApplicationStatusEnum::CONTRACT_SENT,
                           CreditApplicationStatusEnum::CONTRACT_SIGNED,
                           CreditApplicationStatusEnum::CONTRACT_VALIDATED,
                           CreditApplicationStatusEnum::APPROVED
                       ]);
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $approveAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            ->add(Crud::PAGE_INDEX, $manageFees)
            ->add(Crud::PAGE_INDEX, $viewFees)
            ->add(Crud::PAGE_INDEX, $generateContractAction)
            ->add(Crud::PAGE_INDEX, $markAsSignedAction)
            ->add(Crud::PAGE_INDEX, $validateContractAction)
            ->add(Crud::PAGE_INDEX, $disburseFundsAction)
            ->add(Crud::PAGE_INDEX, $downloadContractAction)
            ->add(Crud::PAGE_INDEX, $viewContractAction)
            ->add(Crud::PAGE_INDEX, $resendContractAction)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction)
            ->add(Crud::PAGE_DETAIL, $manageFees)
            ->add(Crud::PAGE_DETAIL, $viewFees)
            ->add(Crud::PAGE_DETAIL, $generateContractAction)
            ->add(Crud::PAGE_DETAIL, $markAsSignedAction)
            ->add(Crud::PAGE_DETAIL, $validateContractAction)
            ->add(Crud::PAGE_DETAIL, $disburseFundsAction)
            ->add(Crud::PAGE_DETAIL, $downloadContractAction)
            ->add(Crud::PAGE_DETAIL, $viewContractAction)
            ->add(Crud::PAGE_DETAIL, $resendContractAction);
    }

    public function approveApplication(AdminContext $context, Request $request): Response
    {
        $creditApplication = $context->getEntity()->getInstance();
        $adminNotes = $request->query->get('notes', 'Demande approuvée le ' . date('d/m/Y H:i'));

        try {
            $this->creditWorkflowService->approveApplication($creditApplication, $adminNotes);
            $this->addFlash('success', 'Demande approuvée avec succès. Le client va recevoir un email et le contrat sera généré automatiquement.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'approbation : ' . $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function rejectApplication(AdminContext $context, Request $request): Response
    {
        $creditApplication = $context->getEntity()->getInstance();
        $adminNotes = $request->query->get('notes', 'Demande rejetée le ' . date('d/m/Y H:i'));

        try {
            $this->creditWorkflowService->rejectApplication($creditApplication, $adminNotes);
            $this->addFlash('success', 'Demande rejetée. Le client va recevoir un email de notification.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du rejet : ' . $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function generateContract(AdminContext $context): Response
    {
        $creditApplication = $context->getEntity()->getInstance();

        // Vérifier que tous les frais obligatoires sont définis
        if (!$creditApplication->canGenerateContract()) {
            $this->addFlash('danger', 'Impossible de générer le contrat : tous les frais obligatoires doivent être définis avant la génération.');
            
            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl();

            return $this->redirect($url);
        }

        try {
            $this->creditWorkflowService->generateAndSendContract($creditApplication);
            $this->addFlash('success', 'Contrat généré et envoyé avec succès au client.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la génération du contrat : ' . $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function markAsSigned(AdminContext $context): Response
    {
        $creditApplication = $context->getEntity()->getInstance();

        try {
            $this->creditWorkflowService->markContractAsSigned($creditApplication);
            $this->addFlash('success', 'Contrat marqué comme signé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du marquage : ' . $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function validateContract(AdminContext $context): Response
    {
        $creditApplication = $context->getEntity()->getInstance();

        try {
            $this->creditWorkflowService->validateContract($creditApplication);
            $this->addFlash('success', 'Contrat validé avec succès. Vous pouvez maintenant débloquer les fonds.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la validation : ' . $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function disburseFunds(AdminContext $context): Response
    {
        $creditApplication = $context->getEntity()->getInstance();

        try {
            $this->creditWorkflowService->disburseFunds($creditApplication);
            $this->addFlash('success', 'Fonds débloqués avec succès. Le montant a été ajouté au compte crédit du client.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du déblocage des fonds : ' . $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function downloadContract(AdminContext $context): Response
    {
        $creditApplication = $context->getEntity()->getInstance();

        // Vérifier que le contrat existe
        if (!$this->creditWorkflowService->contractFileExists($creditApplication)) {
            $this->addFlash('danger', 'Le contrat n\'existe pas ou n\'a pas encore été généré.');
            
            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl();
                
            return $this->redirect($url);
        }

        // Rediriger vers le contrôleur de téléchargement
        return $this->redirectToRoute('contract_download', ['id' => $creditApplication->getId()]);
    }

    public function viewContract(AdminContext $context): Response
    {
        $creditApplication = $context->getEntity()->getInstance();

        // Vérifier que le contrat existe
        if (!$this->creditWorkflowService->contractFileExists($creditApplication)) {
            $this->addFlash('danger', 'Le contrat n\'existe pas ou n\'a pas encore été généré.');
            
            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl();
                
            return $this->redirect($url);
        }

        // Rediriger vers le contrôleur de visualisation
        return $this->redirectToRoute('contract_view', ['id' => $creditApplication->getId()]);
    }

    public function manageFees(AdminContext $context): Response
    {
        $creditApplication = $context->getEntity()->getInstance();

        // Message simple et direct
        $this->addFlash('success', sprintf(
            'Créez manuellement des frais pour: %s (%s %s)',
            $creditApplication->getReferenceNumber(),
            $creditApplication->getFirstName(),
            $creditApplication->getLastName()
        ));

        // Redirection directe vers l'ajout de frais
        $url = $this->adminUrlGenerator
            ->setController(ContractFeeCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function viewFees(AdminContext $context): Response
    {
        $creditApplication = $context->getEntity()->getInstance();

        // Message informatif
        $this->addFlash('info', sprintf(
            'Frais pour la demande %s (%s %s). Total: %.2f €',
            $creditApplication->getReferenceNumber(),
            $creditApplication->getFirstName(),
            $creditApplication->getLastName(),
            $creditApplication->getTotalAppliedFees()
        ));

        // Rediriger vers la liste des frais
        $url = $this->adminUrlGenerator
            ->setController(ContractFeeCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function resendContract(AdminContext $context): Response
    {
        $creditApplication = $context->getEntity()->getInstance();

        // Vérifier que le contrat existe
        if (!$this->creditWorkflowService->contractFileExists($creditApplication)) {
            $this->addFlash('danger', 'Le contrat n\'existe pas. Veuillez le générer d\'abord.');
            
            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl();
                
            return $this->redirect($url);
        }

        try {
            // Renvoyer le contrat par email
            $this->creditWorkflowService->sendContractByEmail($creditApplication);
            
            // Message de succès avec compteur
            $resendCount = $creditApplication->getResendCount() ?? 0;
            $this->addFlash('success', sprintf(
                'Contrat renvoyé avec succès à %s (%s %s). Nombre total d\'envois: %d',
                $creditApplication->getEmail(),
                $creditApplication->getFirstName(),
                $creditApplication->getLastName(),
                $resendCount + 1
            ));
            
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du renvoi du contrat : ' . $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }
}
