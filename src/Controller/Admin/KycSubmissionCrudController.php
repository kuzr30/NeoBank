<?php

namespace App\Controller\Admin;

use App\Entity\KycSubmission;
use App\Service\KycService;
use App\Service\ProfessionalTranslationService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class KycSubmissionCrudController extends AbstractCrudController
{
    public function __construct(
        private KycService $kycService,
        private AdminUrlGenerator $adminUrlGenerator,
        private ProfessionalTranslationService $translationService
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return KycSubmission::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Demandes KYC')
            ->setEntityLabelInSingular('Demande KYC')
            ->setDefaultSort(['submittedAt' => 'DESC'])
            ->setPageTitle('index', 'Gestion des demandes KYC')
            ->setPageTitle('detail', fn (KycSubmission $submission) => sprintf('KYC #%d - %s', $submission->getId(), $submission->getUser()->getEmail()))
            ->showEntityActionsInlined(true);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnIndex();
        
        yield AssociationField::new('user', 'Utilisateur')
            ->setCustomOption('link', true)
            ->formatValue(function ($value, $entity) {
                return sprintf('%s %s (%s)', 
                    $entity->getUser()->getFirstName(),
                    $entity->getUser()->getLastName(),
                    $entity->getUser()->getEmail()
                );
            });

        yield ChoiceField::new('status', 'Statut')
            ->setChoices(KycSubmission::getStatusChoices())
            ->renderAsBadges([
                KycSubmission::STATUS_PENDING => 'warning',
                KycSubmission::STATUS_APPROVED => 'success', 
                KycSubmission::STATUS_REJECTED => 'danger',
                KycSubmission::STATUS_INCOMPLETE => 'secondary'
            ]);

        yield DateTimeField::new('submittedAt', 'Soumis le')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideWhenCreating()
            ->hideWhenUpdating();

        yield DateTimeField::new('processedAt', 'Traité le')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->onlyOnDetail();

        yield AssociationField::new('processedBy', 'Traité par')
            ->onlyOnDetail();

        yield TextareaField::new('adminNotes', 'Notes administratives')
            ->hideOnIndex()
            ->setNumOfRows(4);

        // Affichage des documents sur la page de détail
        if ($pageName === Crud::PAGE_DETAIL) {
            yield AssociationField::new('documents', 'Documents')
                ->setTemplatePath('admin/crud/kyc_documents.html.twig');
        }
    }

    public function configureActions(Actions $actions): Actions
    {
        $approveAction = Action::new('approve', 'Approuver', 'fa fa-check')
            ->linkToCrudAction('approve')
            ->displayIf(fn (KycSubmission $submission) => $submission->isPending())
            ->setCssClass('btn btn-success');

        $rejectAction = Action::new('reject', 'Rejeter', 'fa fa-times')
            ->linkToCrudAction('reject')
            ->displayIf(fn (KycSubmission $submission) => $submission->isPending())
            ->setCssClass('btn btn-danger');

        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $approveAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setIcon('fa fa-eye')->setLabel('Voir');
            })
            ->update(Crud::PAGE_DETAIL, Action::INDEX, function (Action $action) {
                return $action->setIcon('fa fa-list')->setLabel('Retour à la liste');
            });
    }

    public function approve(AdminContext $context, Request $request): RedirectResponse
    {
        /** @var KycSubmission $submission */
        $submission = $context->getEntity()->getInstance();
        
        if (!$submission->isPending()) {
            $this->addFlash('danger', 'Cette demande ne peut pas être approuvée.');
            return $this->redirect($this->adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl());
        }

        $notes = $request->query->get('notes', '');
        
        try {
            $this->kycService->approveKycSubmission($submission, $this->getUser(), $notes);
            $this->addFlash('success', sprintf('Demande KYC #%d approuvée avec succès.', $submission->getId()));
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'approbation : ' . $e->getMessage());
        }

        return $this->redirect($this->adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl());
    }

    public function reject(AdminContext $context, Request $request): RedirectResponse
    {
        /** @var KycSubmission $submission */
        $submission = $context->getEntity()->getInstance();
        
        if (!$submission->isPending()) {
            $this->addFlash('danger', 'Cette demande ne peut pas être rejetée.');
            return $this->redirect($this->adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl());
        }

        $defaultReason = 'alerts.rejected.common_reasons.documents_non_conformes';
        $reason = $request->query->get('reason', $defaultReason);
        
        try {
            $this->kycService->rejectKycSubmission($submission, $this->getUser(), $reason);
            $this->addFlash('success', sprintf('Demande KYC #%d rejetée.', $submission->getId()));
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du rejet : ' . $e->getMessage());
        }

        return $this->redirect($this->adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl());
    }
}
