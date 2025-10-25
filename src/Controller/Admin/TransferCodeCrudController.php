<?php

namespace App\Controller\Admin;

use App\Entity\TransferCode;
use App\Service\TransferManager;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

class TransferCodeCrudController extends AbstractCrudController
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
        return TransferCode::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Code de sécurité')
            ->setEntityLabelInPlural('Codes de sécurité')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined()
            ->setSearchFields(['codeName', 'codeValue', 'transfer.user.email', 'status']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $generateCodeAction = Action::new('generateCodeFromTransfer', 'Générer code pour virement', 'fa fa-key')
            ->linkToCrudAction('generateCodeFromTransfer')
            ->addCssClass('btn btn-primary')
            ->displayIf(static function ($entity) {
                return $entity instanceof TransferCode && 
                       $entity->getTransfer() && 
                       $entity->getTransfer()->getStatus() === 'pending';
            });

        return $actions
            // Les codes sont créés automatiquement, pas de création manuelle
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $generateCodeAction);
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
            
            TextField::new('codeName', 'Nom du code')
                ->setColumns(3),
            
            TextField::new('codeValue', 'Valeur du code')
                ->setColumns(3)
                ->formatValue(function ($value, $entity) {
                    // Afficher le code complet pour l'administration
                    return $value ?: 'N/A';
                }),
            
            // Informations du client
            TextField::new('transfer.user.firstName', 'Prénom client')
                ->setColumns(2)
                ->onlyOnIndex(),
            
            TextField::new('transfer.user.lastName', 'Nom client')
                ->setColumns(2)
                ->onlyOnIndex(),
            
            TextField::new('transfer.user.email', 'Email client')
                ->setColumns(3)
                ->onlyOnIndex(),
            
            IntegerField::new('codeOrder', 'Ordre')
                ->setColumns(2),
            
            TextField::new('status', 'Statut')
                ->setColumns(3)
                ->formatValue(function ($value, $entity) {
                    $statusLabels = [
                        'pending' => 'En attente',
                        'validated' => 'Validé',
                        'expired' => 'Expiré',
                        'blocked' => 'Bloqué'
                    ];
                    return $statusLabels[$value] ?? $value;
                }),
            
            IntegerField::new('failedAttempts', 'Tentatives échouées')
                ->setColumns(3),
            
            DateTimeField::new('createdAt', 'Créé le')
                ->setColumns(3),
            
            DateTimeField::new('validatedAt', 'Validé le')
                ->setColumns(3)
                ->onlyOnDetail(),
            
            DateTimeField::new('expiresAt', 'Expire le')
                ->setColumns(3)
                ->onlyOnDetail(),
            
            // Afficher les informations du virement associé
            TextField::new('transfer.user.email', 'Email utilisateur')
                ->onlyOnDetail(),
            
            TextField::new('transfer.status', 'Statut du virement')
                ->onlyOnDetail(),
        ];
    }

    public function generateCodeFromTransfer(AdminContext $context): Response
    {
        $transferCode = $context->getEntity()->getInstance();
        $transfer = $transferCode->getTransfer();
        
        if (!$transfer || $transfer->getStatus() !== 'pending') {
            $this->addFlash('danger', 'Impossible de générer un code pour ce virement.');
            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl();
            return $this->redirect($url);
        }

        try {
            $code = $this->transferManager->generateCode($transfer);
            $codeValue = $code->getCodeValue();
            $this->addFlash('success', 'Nouveau code généré automatiquement : ' . $codeValue);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la génération : ' . $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
        return $this->redirect($url);
    }
}
