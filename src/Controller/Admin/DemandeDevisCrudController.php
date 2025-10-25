<?php

namespace App\Controller\Admin;

use App\Entity\DemandeDevis;
use App\Enum\DemandeDevisStatusEnum;
use App\Enum\AssuranceType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField as BaseTextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use App\Service\DemandeDevisCreditAssociationService;
use App\Message\DevisApprovalEmailMessage;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class DemandeDevisCrudController extends AbstractCrudController
{
    public function __construct(
        private DemandeDevisCreditAssociationService $associationService,
        private MessageBusInterface $messageBus,
        private UserRepository $userRepository
    ) {}

    public static function getEntityFqcn(): string
    {
        return DemandeDevis::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Demande de devis')
            ->setEntityLabelInPlural('Demandes de devis')
            ->setSearchFields(['numeroDevis', 'nom', 'prenom', 'email', 'telephone'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $approveAction = Action::new('approve', 'Approuver', 'fa fa-check')
            ->linkToCrudAction('approve')
            ->displayIf(function (DemandeDevis $entity) {
                return $entity->canBeApproved();
            })
            ->setCssClass('btn btn-success');

        $rejectAction = Action::new('reject', 'Refuser', 'fa fa-times')
            ->linkToCrudAction('reject')
            ->displayIf(function (DemandeDevis $entity) {
                return $entity->canBeRejected();
            })
            ->setCssClass('btn btn-danger');

        $generateContractAction = Action::new('generateContract', 'Générer contrat', 'fa fa-file-contract')
            ->linkToCrudAction('generateContract')
            ->displayIf(function (DemandeDevis $entity) {
                return $entity->isApproved();
            })
            ->setCssClass('btn btn-primary');

        return $actions
            ->add(Crud::PAGE_INDEX, $approveAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            ->add(Crud::PAGE_INDEX, $generateContractAction)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction)
            ->add(Crud::PAGE_DETAIL, $generateContractAction);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm()->hideOnIndex(),
            TextField::new('numeroDevis', 'Numéro de devis')
                ->setDisabled()
                ->hideOnForm(),
            TextField::new('nom', 'Nom'),
            TextField::new('prenom', 'Prénom'),
            EmailField::new('email', 'Email'),
            TelephoneField::new('telephone', 'Téléphone')->hideOnIndex(),
            ChoiceField::new('typeAssurance', 'Type d\'assurance')
                ->setChoices([
                    'Assurance Auto' => AssuranceType::AUTO,
                    'Assurance Habitation' => AssuranceType::HABITATION,
                    'Assurance Santé' => AssuranceType::SANTE,
                    'Assurance Vie' => AssuranceType::VIE,
                    'Assurance Prêt' => AssuranceType::PRET,
                    'Assurance Voyage' => AssuranceType::VOYAGE,
                    'Assurance Professionnelle' => AssuranceType::PRO,
                    'Assurance Cyber' => AssuranceType::CYBER,
                    'Assurance Décennale' => AssuranceType::DECENNALE,
                    'Responsabilité Civile' => AssuranceType::RC,
                    'Assurance Flotte' => AssuranceType::FLOTTE,
                    'Assurance Garage' => AssuranceType::GARAGE
                ])
                ->formatValue(function ($value) {
                    return $value instanceof AssuranceType ? $value->getLabel() : $value;
                }),
            ChoiceField::new('preferenceContact', 'Préférence de contact')
                ->setChoices([
                    'Par téléphone' => 'telephone',
                    'Par email' => 'email',
                    'Indifférent' => 'indifferent'
                ])->hideOnIndex(),
            TextareaField::new('commentaires', 'Commentaires')
                ->hideOnIndex(),
            ChoiceField::new('statut', 'Statut')
                ->setChoices([
                    'En attente' => DemandeDevisStatusEnum::EN_ATTENTE,
                    'En cours' => DemandeDevisStatusEnum::EN_COURS,
                    'Approuvé' => DemandeDevisStatusEnum::APPROUVE,
                    'Refusé' => DemandeDevisStatusEnum::REFUSE,
                    'Traité' => DemandeDevisStatusEnum::TRAITE,
                    'Expiré' => DemandeDevisStatusEnum::EXPIRE
                ])
                ->renderAsBadges([
                    DemandeDevisStatusEnum::EN_ATTENTE->value => 'warning',
                    DemandeDevisStatusEnum::EN_COURS->value => 'info',
                    DemandeDevisStatusEnum::APPROUVE->value => 'success',
                    DemandeDevisStatusEnum::REFUSE->value => 'danger',
                    DemandeDevisStatusEnum::TRAITE->value => 'secondary',
                    DemandeDevisStatusEnum::EXPIRE->value => 'dark'
                ])
                ->formatValue(function ($value) {
                    return $value instanceof DemandeDevisStatusEnum ? $value->getLabel() : $value;
                }),
            DateTimeField::new('createdAt', 'Date de création')
                ->setDisabled()
                ->hideOnForm()->hideOnIndex(),
            DateTimeField::new('updatedAt', 'Dernière mise à jour')
                ->hideOnForm()
                ->hideOnIndex(),
            BaseTextField::new('creditAssociations', 'Crédits associés')
                ->onlyOnDetail()
                ->formatValue(function ($value, $entity) {
                    /** @var DemandeDevis $entity */
                    $creditInfo = $this->associationService->getCreditInfoForDemandeDevis($entity);
                    
                    if (empty($creditInfo)) {
                        return '<em>Aucun crédit associé</em>';
                    }
                    
                    $html = '<ul>';
                    foreach ($creditInfo as $info) {
                        $html .= sprintf(
                            '<li><strong>%s</strong> - %s € (%d mois) - <span class="badge badge-info">%s</span></li>',
                            $info['reference'],
                            number_format($info['amount'], 2, ',', ' '),
                            $info['duration'],
                            $info['status']
                        );
                    }
                    $html .= '</ul>';
                    
                    return $html;
                }),
        ];
    }

    public function approve(AdminContext $context)
    {
        /** @var DemandeDevis $demandeDevis */
        $demandeDevis = $context->getEntity()->getInstance();
        
        try {
            $demandeDevis->approve();
            $this->container->get('doctrine')->getManagerForClass(DemandeDevis::class)->flush();
            
            // Détection de la langue du client
            $locale = 'fr'; // Langue par défaut
            
            // Chercher l'utilisateur par email pour obtenir sa langue préférée
            $user = $this->userRepository->findOneBy(['email' => $demandeDevis->getEmail()]);
            if ($user && $user->getLanguage()) {
                $locale = $user->getLanguage();
            } else {
                // Fallback sur la langue de la requête actuelle si disponible
                $locale = $this->container->get('request_stack')->getCurrentRequest()?->getLocale() ?? 'fr';
            }
            
            // Envoyer l'email d'approbation via le système de messages asynchrone
            $approvalMessage = new DevisApprovalEmailMessage($demandeDevis->getId(), $locale);
            $this->messageBus->dispatch($approvalMessage);
            
            $this->addFlash('success', 'La demande de devis a été approuvée avec succès. Un email de confirmation a été envoyé au client.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'approbation : ' . $e->getMessage());
        }
        
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
            
        return $this->redirect($url);
    }

    public function reject(AdminContext $context)
    {
        /** @var DemandeDevis $demandeDevis */
        $demandeDevis = $context->getEntity()->getInstance();
        
        try {
            $demandeDevis->reject();
            $this->container->get('doctrine')->getManagerForClass(DemandeDevis::class)->flush();
            $this->addFlash('success', 'La demande de devis a été refusée.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du refus : ' . $e->getMessage());
        }
        
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
            
        return $this->redirect($url);
    }

    public function generateContract(AdminContext $context)
    {
        /** @var DemandeDevis $demandeDevis */
        $demandeDevis = $context->getEntity()->getInstance();
        
        try {
            $entityManager = $this->container->get('doctrine')->getManagerForClass(DemandeDevis::class);
            
            // Vérifier si un contrat existe déjà pour cette demande de devis
            $existingContract = $entityManager->getRepository(\App\Entity\ContratAssurance::class)
                ->findOneBy(['demandeDevis' => $demandeDevis]);
            
            if ($existingContract) {
                $this->addFlash('warning', 'Un contrat existe déjà pour cette demande de devis.');
                
                // Rediriger vers le contrat existant
                $url = $this->container->get(AdminUrlGenerator::class)
                    ->setController(\App\Controller\Admin\ContratAssuranceCrudController::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($existingContract->getId())
                    ->generateUrl();
                    
                return $this->redirect($url);
            }
            
            // Chercher l'utilisateur par email
            $userRepository = $entityManager->getRepository(\App\Entity\User::class);
            $user = $userRepository->findOneBy(['email' => $demandeDevis->getEmail()]);
            
            if (!$user) {
                $this->addFlash('danger', 'Aucun utilisateur trouvé avec l\'email ' . $demandeDevis->getEmail());
                
                $url = $this->container->get(AdminUrlGenerator::class)
                    ->setController(self::class)
                    ->setAction(Action::INDEX)
                    ->generateUrl();
                    
                return $this->redirect($url);
            }
            
            // Créer le nouveau contrat
            $contrat = new \App\Entity\ContratAssurance();
            $contrat->setUser($user);
            $contrat->setDemandeDevis($demandeDevis);
            $contrat->setTypeAssurance($demandeDevis->getTypeAssurance());
            $contrat->setStatut(\App\Enum\ContratAssuranceStatusEnum::ACTIF);
            $contrat->setDateActivation(new \DateTimeImmutable());
            
            // Définir une prime mensuelle par défaut selon le type d'assurance
            $primeDefaut = match($demandeDevis->getTypeAssurance()) {
                \App\Enum\AssuranceType::AUTO => '50.00',
                \App\Enum\AssuranceType::HABITATION => '30.00',
                \App\Enum\AssuranceType::SANTE => '80.00',
                \App\Enum\AssuranceType::VIE => '100.00',
                \App\Enum\AssuranceType::PRET => '25.00',
                \App\Enum\AssuranceType::VOYAGE => '15.00',
                \App\Enum\AssuranceType::PRO => '150.00',
                \App\Enum\AssuranceType::CYBER => '200.00',
                \App\Enum\AssuranceType::DECENNALE => '300.00',
                \App\Enum\AssuranceType::RC => '40.00',
                \App\Enum\AssuranceType::FLOTTE => '500.00',
                \App\Enum\AssuranceType::GARAGE => '250.00',
                default => '50.00'
            };
            
            $contrat->setPrimeMensuelle($primeDefaut);
            
            // Sauvegarder le contrat
            $entityManager->persist($contrat);
            $entityManager->flush();
            
            $this->addFlash('success', 'Contrat d\'assurance généré avec succès ! Vous pouvez maintenant le finaliser.');
            
            // Rediriger vers l'édition du contrat créé
            $url = $this->container->get(AdminUrlGenerator::class)
                ->setController(\App\Controller\Admin\ContratAssuranceCrudController::class)
                ->setAction(Action::EDIT)
                ->setEntityId($contrat->getId())
                ->generateUrl();
                
            return $this->redirect($url);
            
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la génération du contrat : ' . $e->getMessage());
        }
        
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
            
        return $this->redirect($url);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var DemandeDevis $demandeDevis */
        $demandeDevis = $entityInstance;
        
        // Persister d'abord la demande de devis
        parent::persistEntity($entityManager, $entityInstance);
        
        // Tenter l'association automatique avec la dernière demande de crédit
        try {
            $association = $this->associationService->autoAssociateWithLatestCredit($demandeDevis);
            
            if ($association) {
                $this->addFlash('success', sprintf(
                    'Demande de devis créée et automatiquement associée à la demande de crédit %s',
                    $association->getCreditApplication()->getReferenceNumber()
                ));
            } else {
                $this->addFlash('info', 'Demande de devis créée. Aucune demande de crédit trouvée pour cet email.');
            }
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Demande de devis créée mais erreur lors de l\'association automatique : ' . $e->getMessage());
        }
    }
}