<?php

namespace App\Controller\Admin;

use App\Entity\Transfer;
use App\Service\TransferManager;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TransferCrudController extends AbstractCrudController
{
    private TransferManager $transferManager;
    private AdminUrlGenerator $adminUrlGenerator;

    public function __construct(TransferManager $transferManager, AdminUrlGenerator $adminUrlGenerator)
    {
        $this->transferManager = $transferManager;
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public static function getEntityFqcn(): string
    {
        return Transfer::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Virement')
            ->setEntityLabelInPlural('Virements')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined()
            ->setSearchFields(['user.email', 'user.firstName', 'user.lastName', 'destinationAccount.iban', 'destinationAccount.accountName', 'amount', 'description', 'status']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $generateCodeAction = Action::new('generateCode', 'Générer code', 'fa fa-key')
            ->linkToCrudAction('generateCode')
            ->addCssClass('btn btn-primary')
            ->displayIf(static function (Transfer $entity) {
                return in_array($entity->getStatus(), ['pending', 'executing']);
            });

        $simulateValidationAction = Action::new('simulateValidation', 'Valider le virement', 'fa fa-play')
            ->linkToCrudAction('simulateValidation')
            ->addCssClass('btn btn-warning')
            ->displayIf(static function (Transfer $entity) {
                return $entity->getStatus() === 'executing' || ($entity->getStatus() === 'pending' && $entity->getCurrentCode());
            });

        $validateAction = Action::new('validateTransfer', 'Valider final', 'fa fa-check')
            ->linkToCrudAction('validateTransfer')
            ->addCssClass('btn btn-success')
            ->displayIf(static function (Transfer $entity) {
                return in_array($entity->getStatus(), ['pending', 'executing']);
            });

        $cancelAction = Action::new('cancelTransfer', 'Annuler', 'fa fa-times')
            ->linkToCrudAction('cancelTransfer')
            ->addCssClass('btn btn-warning')
            ->displayIf(static function (Transfer $entity) {
                return in_array($entity->getStatus(), ['pending', 'executing']);
            });

        $blockAction = Action::new('blockTransfer', 'Bloquer', 'fa fa-ban')
            ->linkToCrudAction('blockTransfer')
            ->addCssClass('btn btn-danger')
            ->displayIf(static function (Transfer $entity) {
                return in_array($entity->getStatus(), ['pending', 'executing']) && $entity->getFailedAttemptsTotal() >= 3;
            });

        $unblockAction = Action::new('unblockTransfer', 'Débloquer', 'fa fa-unlock')
            ->linkToCrudAction('unblockTransfer')
            ->addCssClass('btn btn-info')
            ->displayIf(static function (Transfer $entity) {
                return $entity->getStatus() === 'blocked';
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $generateCodeAction)
            ->add(Crud::PAGE_INDEX, $simulateValidationAction)
            ->add(Crud::PAGE_INDEX, $validateAction)
            ->add(Crud::PAGE_INDEX, $cancelAction)
            ->add(Crud::PAGE_INDEX, $blockAction)
            ->add(Crud::PAGE_INDEX, $unblockAction)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('user', 'Utilisateur')
            ->setCrudController(UserCrudController::class)
            ->setFormTypeOption('choice_label', function ($user) {
                return $user->getFirstName() . ' ' . $user->getLastName() . ' (' . $user->getEmail() . ')';
            });
        yield NumberField::new('amount', 'Montant')
            ->setNumDecimals(2)
            ->formatValue(function ($value) {
                return number_format($value, 2, ',', ' ') . ' €';
            });
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'En attente' => 'pending',
                'En cours' => 'executing',
                'Terminé' => 'completed',
                'Expiré' => 'expired',
                'Annulé' => 'cancelled',
                'Bloqué' => 'blocked'
            ])
            ->renderAsBadges([
                'pending' => 'warning',
                'executing' => 'info',
                'completed' => 'success',
                'expired' => 'danger',
                'cancelled' => 'secondary',
                'blocked' => 'danger'
            ]);
        yield AssociationField::new('transferCodes', 'Codes de validation')
            ->hideOnForm()
            ->setTemplatePath('admin/field/transfer_codes.html.twig');
        yield DateTimeField::new('createdAt', 'Date de création')->hideOnForm()->hideOnIndex();
        yield DateTimeField::new('updatedAt', 'Dernière modification')->hideOnForm()->hideOnIndex();
        yield DateTimeField::new('expiresAt', 'Expire le');
        yield IntegerField::new('failedAttemptsTotal', 'Tentatives échouées')->hideOnForm();
        yield BooleanField::new('isAccountBlocked', 'Compte bloqué')->hideOnForm();
    }

    public function generateCode(AdminContext $context): Response
    {
        $transfer = $context->getEntity()->getInstance();
        
        if (!in_array($transfer->getStatus(), ['pending', 'executing'])) {
            $this->addFlash('danger', 'Impossible de générer un code pour ce virement.');
            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl();
            return $this->redirect($url);
        }

        $code = $this->transferManager->generateCode($transfer);
        $codeValue = $code->getCodeValue();
        $statusMessage = $transfer->getStatus() === 'executing' ? ' (virement remis en attente)' : '';
        $this->addFlash('success', 'Code généré automatiquement : ' . $codeValue . $statusMessage);

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
        return $this->redirect($url);
    }

    public function generateManualCode(AdminContext $context): Response
    {
        $transfer = $context->getEntity()->getInstance();
        
        if (!in_array($transfer->getStatus(), ['pending', 'executing'])) {
            $this->addFlash('danger', 'Impossible de générer un code pour ce virement.');
            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl();
            return $this->redirect($url);
        }

        $code = $this->transferManager->generateManualCode($transfer);
        $codeValue = $code->getCodeValue();
        $statusMessage = $transfer->getStatus() === 'executing' ? ' (virement remis en attente)' : '';
        $this->addFlash('success', 'Code manuel généré : ' . $codeValue . $statusMessage);

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
        return $this->redirect($url);
    }

    public function validateTransfer(AdminContext $context): Response
    {
        $transfer = $context->getEntity()->getInstance();
        
        if (!in_array($transfer->getStatus(), ['pending', 'executing'])) {
            $this->addFlash('danger', 'Impossible de valider ce virement.');
            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl();
            return $this->redirect($url);
        }

        try {
            // Force la validation du virement (bypass des codes)
            $this->transferManager->forceValidateTransfer($transfer);
            $this->addFlash('success', 'Virement validé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la validation : ' . $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
        return $this->redirect($url);
    }

    public function cancelTransfer(AdminContext $context): Response
    {
        $transfer = $context->getEntity()->getInstance();
        
        if (!in_array($transfer->getStatus(), ['pending', 'executing'])) {
            $this->addFlash('danger', 'Impossible d\'annuler ce virement.');
            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl();
            return $this->redirect($url);
        }

        try {
            $this->transferManager->cancelTransfer($transfer);
            $this->addFlash('success', 'Virement annulé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'annulation : ' . $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
        return $this->redirect($url);
    }

    public function blockTransfer(AdminContext $context): Response
    {
        $transfer = $context->getEntity()->getInstance();
        
        if (!in_array($transfer->getStatus(), ['pending', 'executing'])) {
            $this->addFlash('danger', 'Impossible de bloquer ce virement.');
            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl();
            return $this->redirect($url);
        }

        try {
            $this->transferManager->blockTransfer($transfer);
            $this->addFlash('success', 'Virement bloqué avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du blocage : ' . $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
        return $this->redirect($url);
    }

    public function unblockTransfer(AdminContext $context): Response
    {
        $transfer = $context->getEntity()->getInstance();
        
        if ($transfer->getStatus() !== 'blocked') {
            $this->addFlash('danger', 'Ce virement n\'est pas bloqué.');
            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl();
            return $this->redirect($url);
        }

        try {
            $this->transferManager->unblockTransfer($transfer);
            $this->addFlash('success', 'Virement débloqué avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du déblocage : ' . $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
        return $this->redirect($url);
    }

    public function simulateValidation(AdminContext $context): Response
    {
        $transfer = $context->getEntity()->getInstance();
        
        if (!in_array($transfer->getStatus(), ['pending', 'executing'])) {
            $this->addFlash('danger', 'Impossible de simuler la validation pour ce virement.');
            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl();
            return $this->redirect($url);
        }

        try {
            $result = $this->transferManager->simulateUserValidation($transfer);
            
            if ($result['success']) {
                $this->addFlash('success', $result['message']);
                
                if ($result['next_code_needed']) {
                    $this->addFlash('info', 'Vous pouvez maintenant générer le prochain code.');
                }
            } else {
                $this->addFlash('danger', $result['message']);
            }
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la simulation : ' . $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
        return $this->redirect($url);
    }
}
