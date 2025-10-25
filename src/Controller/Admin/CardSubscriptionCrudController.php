<?php

namespace App\Controller\Admin;

use App\Entity\CardSubscription;
use App\Service\CardSubscriptionService;
use App\Form\CardFeesType;
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
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CardSubscriptionCrudController extends AbstractCrudController
{
    public function __construct(
        private CardSubscriptionService $cardSubscriptionService
    ) {}

    public static function getEntityFqcn(): string
    {
        return CardSubscription::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Souscription de carte')
            ->setEntityLabelInPlural('Souscriptions de cartes')
            ->setPageTitle('index', 'Gestion des souscriptions de cartes')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(20);
    }

    public function configureActions(Actions $actions): Actions
    {
        // Action pour définir les frais
        $setFeesAction = Action::new('setFees', 'Définir frais', 'fas fa-euro-sign')
            ->linkToCrudAction('setFeesForm')
            ->setCssClass('btn btn-warning')
            ->displayIf(function ($entity) {
                return $entity->getStatus() === 'pending';
            });

        // Action pour approuver avec frais définis
        $approveAction = Action::new('approve', 'Approuver', 'fas fa-check')
            ->linkToCrudAction('approveSubscription')
            ->setCssClass('btn btn-success')
            ->displayIf(function ($entity) {
                return $entity->getStatus() === 'fees_set';
            });

        // Action personnalisée pour valider le paiement (création de carte)
        $validateAction = Action::new('validate', 'Valider paiement', 'fas fa-credit-card')
            ->linkToCrudAction('validateSubscription')
            ->setCssClass('btn btn-primary')
            ->displayIf(function ($entity) {
                return $entity->getStatus() === 'signed';
            });

        // Action personnalisée pour rejeter une souscription
        $rejectAction = Action::new('reject', 'Rejeter', 'fas fa-times')
            ->linkToCrudAction('rejectSubscription')
            ->setCssClass('btn btn-danger')
            ->displayIf(function ($entity) {
                return in_array($entity->getStatus(), ['pending', 'fees_set']);
            });

        // Action pour voir le contrat
        $viewContractAction = Action::new('viewContract', 'Voir contrat', 'fas fa-file-contract')
            ->linkToRoute('card_contract_download', function ($entity) {
                return ['reference' => $entity->getContract()?->getReference()];
            })
            ->setHtmlAttributes(['target' => '_blank'])
            ->displayIf(function ($entity) {
                return $entity->getContract() !== null;
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $setFeesAction)
            ->add(Crud::PAGE_INDEX, $approveAction)
            ->add(Crud::PAGE_INDEX, $validateAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            ->add(Crud::PAGE_INDEX, $viewContractAction)
            ->add(Crud::PAGE_DETAIL, $setFeesAction)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_DETAIL, $validateAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction)
            ->add(Crud::PAGE_DETAIL, $viewContractAction)
            ->disable(Action::NEW, Action::EDIT)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm()->hideOnIndex(),
            
            AssociationField::new('user', 'Client')
                ->formatValue(function ($value, $entity) {
                    $user = $entity->getUser();
                    $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
                    $url = $adminUrlGenerator
                        ->setRoute('app_admin_dashboard')
                        ->setController(UserCrudController::class)
                        ->setAction(Action::DETAIL)
                        ->setEntityId($user->getId())
                        ->generateUrl();
                    
                    return sprintf('<a href="%s" class="text-primary">%s %s</a>', 
                        $url, 
                        $user->getFirstName(), 
                        $user->getLastName()
                    );
                }),
            
            AssociationField::new('account', 'Compte')
                ->setFormattedValue(function ($value, $entity) {
                    $account = $entity->getAccount();
                    return $account ? $account->getAccountNumber() : '';
                })->hideOnIndex(),
            
            TextField::new('cardType', 'Type de carte'),
            TextField::new('cardBrand', 'Marque'),
            
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'En attente' => 'pending',
                    'Frais définis' => 'fees_set',
                    'Approuvée' => 'approved',
                    'Contrat signé' => 'signed',
                    'Paiement en attente' => 'payment_pending',
                    'Active' => 'active',
                    'Rejetée' => 'rejected',
                ])
                ->renderAsBadges([
                    'pending' => 'warning',
                    'fees_set' => 'info',
                    'approved' => 'primary',
                    'signed' => 'success',
                    'payment_pending' => 'warning',
                    'active' => 'success',
                    'rejected' => 'danger',
                ]),
            
            MoneyField::new('activationFee', 'Frais d\'activation')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->hideOnIndex(),
            
            MoneyField::new('monthlyFee', 'Frais mensuels')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->hideOnIndex(),
            
            MoneyField::new('dailyLimit', 'Limite journalière')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->hideOnIndex(),
            
            MoneyField::new('monthlyLimit', 'Limite mensuelle')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            
            MoneyField::new('creditLimit', 'Limite de crédit')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->hideOnIndex(),
            
            AssociationField::new('contract', 'Contrat')
                ->setFormattedValue(function ($value, $entity) {
                    $contract = $entity->getContract();
                    if (!$contract) return 'Aucun contrat';
                    
                    $statusLabels = [
                        'pending' => '⏳ En préparation',
                        'sent' => '📧 Envoyé',
                        'signed' => '✅ Signé',
                        'expired' => '❌ Expiré'
                    ];
                    
                    return $contract->getReference() . ' - ' . ($statusLabels[$contract->getStatus()] ?? $contract->getStatus());
                }),
            
            DateTimeField::new('createdAt', 'Date de création')
                ->setFormat('dd/MM/yyyy HH:mm')->hideOnIndex(),
            
            DateTimeField::new('updatedAt', 'Dernière modification')
                ->setFormat('dd/MM/yyyy HH:mm')
                ->hideOnIndex(),
            
            TextareaField::new('reason', 'Motif')
                ->hideOnIndex()
                ->setMaxLength(1000),
            
            TextareaField::new('reason', 'Commentaire admin')
                ->hideOnIndex()
                ->setMaxLength(1000),
        ];
    }

    public function validateSubscription(AdminContext $context): RedirectResponse
    {
        $subscription = $context->getEntity()->getInstance();
        
        if (!$subscription instanceof CardSubscription) {
            $this->addFlash('danger', 'Souscription introuvable.');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        try {
            $adminComment = 'Paiement validé et carte activée';
            $card = $this->cardSubscriptionService->validatePaymentAndCreateCard(
                $subscription, 
                $this->getUser(),
                $adminComment
            );
            
            $this->addFlash('success', sprintf(
                'Paiement validé et carte %s créée pour %s %s. La carte est maintenant disponible.',
                $card->getCardNumber(),
                $subscription->getUser()->getFirstName(),
                $subscription->getUser()->getLastName()
            ));
            
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la validation du paiement : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }

    public function rejectSubscription(AdminContext $context): RedirectResponse
    {
        $subscription = $context->getEntity()->getInstance();
        
        if (!$subscription instanceof CardSubscription) {
            $this->addFlash('danger', 'Souscription introuvable.');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        try {
            $adminComment = 'Souscription rejetée par validation administrative';
            $this->cardSubscriptionService->rejectSubscription(
                $subscription,
                $this->getUser(),
                $adminComment
            );
            
            $this->addFlash('success', sprintf(
                'Souscription rejetée pour %s %s.',
                $subscription->getUser()->getFirstName(),
                $subscription->getUser()->getLastName()
            ));
            
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du rejet : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }

    public function setFeesForm(AdminContext $context, Request $request): Response
    {
        $subscription = $context->getEntity()->getInstance();
        
        if (!$subscription instanceof CardSubscription) {
            $this->addFlash('danger', 'Souscription introuvable.');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        if ($subscription->getStatus() !== 'pending') {
            $this->addFlash('warning', 'Les frais ne peuvent être définis que pour les demandes en attente.');
            return $this->redirectToRoute('app_admin_dashboard');
        }

                // Créer le formulaire avec les valeurs par défaut selon le type de carte et la marque
        $cardBrand = $subscription->getCardBrand();
        $cardType = $subscription->getCardType();
        
        // Frais mensuels selon l'image fournie
        $monthlyFeeByBrandAndType = [
            'visa' => [
                'classic' => 0.00,  // Gratuite
                'gold' => 5.00,     // 5€ par mois
                'platinum' => 15.00  // 15€ par mois
            ],
            'mastercard' => [
                'classic' => 2.00,  // 2€ par mois
                'gold' => 8.00,     // 8€ par mois
                'platinum' => 20.00  // 20€ par mois
            ]
        ];
        
        $defaultData = [
            'activationFee' => null, // Laisser vide pour que l'admin définisse
            'monthlyFee' => $monthlyFeeByBrandAndType[$cardBrand][$cardType] ?? 0.00,
            'dailyLimit' => match($cardType) {
                'classic' => 500.00,
                'gold' => 1500.00,
                'platinum' => 3000.00,
                default => 500.00
            },
            'monthlyLimit' => match($cardType) {
                'classic' => 3000.00,
                'gold' => 5000.00,
                'platinum' => 10000.00,
                default => 3000.00
            },
            'creditLimit' => match($cardType) {
                'classic' => 0.00,
                'gold' => 200.00,
                'platinum' => 3000.00,
                default => 0.00
            }
        ];

        $form = $this->createForm(CardFeesType::class, $defaultData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            try {
                // Appliquer les frais saisis par l'admin
                $subscription->setActivationFee($data['activationFee']);
                $subscription->setMonthlyFee($data['monthlyFee']);
                $subscription->setDailyLimit($data['dailyLimit']);
                $subscription->setMonthlyLimit($data['monthlyLimit']);
                $subscription->setCreditLimit($data['creditLimit']);
                $subscription->setStatus('fees_set');
                $subscription->setProcessedBy($this->getUser());
                $subscription->setProcessedAt(new \DateTimeImmutable());

                $this->cardSubscriptionService->persistSubscription($subscription);

                $this->addFlash('success', sprintf(
                    'Frais définis avec succès pour la demande %s. Activation: %.2f€, Mensuel: %.2f€',
                    $subscription->getReference(),
                    $data['activationFee'],
                    $data['monthlyFee']
                ));

                return $this->redirectToRoute('app_admin_dashboard');

            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de la définition des frais : ' . $e->getMessage());
            }
        }

        return $this->render('admin/card_subscription/set_fees.html.twig', [
            'subscription' => $subscription,
            'form' => $form->createView(),
        ]);
    }

    public function approveSubscription(AdminContext $context): RedirectResponse
    {
        $subscription = $context->getEntity()->getInstance();
        
        if (!$subscription instanceof CardSubscription) {
            $this->addFlash('danger', 'Souscription introuvable.');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        try {
            $this->cardSubscriptionService->approveSubscription($subscription, $this->getUser());
            
            $this->addFlash('success', sprintf(
                'Souscription approuvée et contrat envoyé à %s %s pour signature.',
                $subscription->getUser()->getFirstName(),
                $subscription->getUser()->getLastName()
            ));
            
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'approbation : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }
}
