<?php

namespace App\Controller\Admin;

use App\Entity\ContractSubscription;
use App\Service\CardSubscriptionService;
use App\Service\ContractSubscriptionService;
use App\Controller\Admin\UserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Doctrine\ORM\EntityManagerInterface;

class ContractSubscriptionCrudController extends AbstractCrudController
{
    public function __construct(
        private ContractSubscriptionService $contractService,
        private CardSubscriptionService $cardSubscriptionService,
        private EntityManagerInterface $entityManager
    ) {}

    public static function getEntityFqcn(): string
    {
        return ContractSubscription::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Contrat de souscription')
            ->setEntityLabelInPlural('Contrats de souscription')
            ->setPageTitle('index', 'Gestion des contrats de souscription')
            ->setPageTitle('detail', 'Détails du contrat %entity_label_singular%')
            ->setDefaultSort(['signedAt' => 'DESC', 'createdAt' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->showEntityActionsInlined();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', 'Statut')
                ->setChoices([
                    'En préparation' => 'pending',
                    'Envoyé' => 'sent',
                    'Signé' => 'signed',
                    'Expiré' => 'expired',
                ]))
            ->add(DateTimeFilter::new('signedAt', 'Date de signature'))
            ->add(DateTimeFilter::new('createdAt', 'Date de création'));
    }

    public function configureActions(Actions $actions): Actions
    {
        // Action pour télécharger le PDF du contrat
        $downloadAction = Action::new('download', 'Télécharger PDF', 'fas fa-download')
            ->linkToRoute('card_contract_download', function ($entity) {
                return ['reference' => $entity->getReference()];
            })
            ->setHtmlAttributes(['target' => '_blank'])
            ->displayIf(function ($entity) {
                return $entity->getContractPdfPath() !== null;
            });

        // Action pour valider un contrat signé
        $approveAction = Action::new('approve', 'Valider', 'fas fa-check-circle')
            ->linkToCrudAction('approve')
            ->setCssClass('btn btn-success')
            ->displayIf(function ($entity) {
                return $entity->isSigned() && $entity->getCardSubscription()->getStatus() === 'pending_validation';
            });

        // Action pour rejeter un contrat
        $rejectAction = Action::new('reject', 'Rejeter', 'fas fa-times-circle')
            ->linkToCrudAction('reject')
            ->setCssClass('btn btn-danger')
            ->displayIf(function ($entity) {
                return $entity->isSigned() && $entity->getCardSubscription()->getStatus() === 'pending_validation';
            });

        // Action pour voir les détails du client
        $viewUserAction = Action::new('viewUser', 'Voir le client', 'fas fa-user')
            ->linkToCrudAction('viewUser')
            ->setHtmlAttributes(['target' => '_blank']);

        return $actions
            ->add(Crud::PAGE_INDEX, $downloadAction)
            ->add(Crud::PAGE_DETAIL, $downloadAction)
            ->add(Crud::PAGE_INDEX, $approveAction)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction)
            ->add(Crud::PAGE_INDEX, $viewUserAction)
            ->add(Crud::PAGE_DETAIL, $viewUserAction)
            ->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            
            TextField::new('reference', 'Référence contrat')
                ->setFormattedValue(function ($value) {
                    return $value ?: 'Non généré';
                }),
            
            AssociationField::new('cardSubscription', 'Souscription')
                ->setFormattedValue(function ($value, $entity) {
                    $subscription = $entity->getCardSubscription();
                    if (!$subscription) return 'Aucune souscription';
                    
                    $user = $subscription->getUser();
                    return sprintf('%s %s - %s %s',
                        $user->getFirstName(),
                        $user->getLastName(),
                        $subscription->getCardType(),
                        $subscription->getCardBrand()
                    );
                }),
            
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'En préparation' => 'pending',
                    'Envoyé' => 'sent',
                    'Signé' => 'signed',
                    'Expiré' => 'expired',
                ])
                ->renderAsBadges([
                    'pending' => 'warning',
                    'sent' => 'info',
                    'signed' => 'success',
                    'expired' => 'danger',
                ]),
            
            MoneyField::new('cardFees', 'Frais carte')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            
            MoneyField::new('dailyLimit', 'Limite journalière')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->hideOnIndex(),
            
            MoneyField::new('monthlyLimit', 'Limite mensuelle')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            
            DateTimeField::new('sentAt', 'Envoyé le')
                ->setFormat('dd/MM/yyyy HH:mm')
                ->hideOnIndex(),
            
            DateTimeField::new('signedAt', 'Signé le')
                ->setFormat('dd/MM/yyyy HH:mm'),
            
            DateTimeField::new('expiresAt', 'Expire le')
                ->setFormat('dd/MM/yyyy HH:mm')
                ->hideOnIndex(),
            
            TextField::new('signatureData', 'Signature électronique')
                ->setFormattedValue(function ($value) {
                    return $value ? '✅ Signature présente' : '❌ Non signé';
                })
                ->hideOnIndex(),
            
            TextareaField::new('generalConditions', 'Conditions générales')
                ->hideOnIndex()
                ->setMaxLength(5000),
            
            TextareaField::new('specificConditions', 'Conditions spécifiques')
                ->hideOnIndex()
                ->setMaxLength(5000),
            
            TextField::new('contractPdfPath', 'Chemin PDF')
                ->setFormattedValue(function ($value) {
                    return $value ? '📄 PDF généré' : '❌ PDF non généré';
                })
                ->hideOnIndex(),
            
            DateTimeField::new('createdAt', 'Date de création')
                ->setFormat('dd/MM/yyyy HH:mm')->hideOnIndex(),
            
            DateTimeField::new('updatedAt', 'Dernière modification')
                ->setFormat('dd/MM/yyyy HH:mm')
                ->hideOnIndex(),
        ];
    }

    /**
     * Action pour valider un contrat signé
     */
    public function approve(AdminContext $context): Response
    {
        /** @var ContractSubscription $contract */
        $contract = $context->getEntity()->getInstance();
        
        if (!$contract->isSigned()) {
            $this->addFlash('error', 'Le contrat doit être signé avant d\'être validé.');
            return $this->redirect($context->getReferrer());
        }

        $subscription = $contract->getCardSubscription();
        if ($subscription->getStatus() !== 'pending_validation') {
            $this->addFlash('error', 'Cette souscription n\'est pas en attente de validation.');
            return $this->redirect($context->getReferrer());
        }

        try {
            // Valider la souscription
            $this->cardSubscriptionService->approveSubscription(
                $subscription, 
                $this->getUser(), 
                'Validé par l\'administrateur'
            );
            
            $this->addFlash('success', sprintf(
                'Contrat %s validé avec succès ! La carte sera produite et envoyée au client.',
                $contract->getReference()
            ));
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la validation : ' . $e->getMessage());
        }

        return $this->redirect($context->getReferrer());
    }

    /**
     * Action pour rejeter un contrat signé
     */
    public function reject(AdminContext $context): Response
    {
        /** @var ContractSubscription $contract */
        $contract = $context->getEntity()->getInstance();
        
        if (!$contract->isSigned()) {
            $this->addFlash('error', 'Le contrat doit être signé avant d\'être rejeté.');
            return $this->redirect($context->getReferrer());
        }

        $subscription = $contract->getCardSubscription();
        if ($subscription->getStatus() !== 'pending_validation') {
            $this->addFlash('error', 'Cette souscription n\'est pas en attente de validation.');
            return $this->redirect($context->getReferrer());
        }

        try {
            // Rejeter la souscription
            $this->cardSubscriptionService->rejectSubscription(
                $subscription, 
                $this->getUser(), 
                'Rejeté par l\'administrateur'
            );
            
            $this->addFlash('warning', sprintf(
                'Contrat %s rejeté. Le client sera notifié par email.',
                $contract->getReference()
            ));
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du rejet : ' . $e->getMessage());
        }

        return $this->redirect($context->getReferrer());
    }

    /**
     * Action pour voir les détails du client
     */
    public function viewUser(AdminContext $context): Response
    {
        /** @var ContractSubscription $contract */
        $contract = $context->getEntity()->getInstance();
        $user = $contract->getCardSubscription()->getUser();
        
        // Rediriger vers la page de détail de l'utilisateur
        return $this->redirectToRoute('app_admin_dashboard', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => UserCrudController::class,
            'entityId' => $user->getId(),
        ]);
    }
}
