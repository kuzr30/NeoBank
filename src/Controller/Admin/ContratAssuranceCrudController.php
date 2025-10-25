<?php

namespace App\Controller\Admin;

use App\Entity\ContratAssurance;
use App\Enum\ContratAssuranceStatusEnum;
use App\Enum\AssuranceType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use App\Service\ContractGeneratorService;
use App\Service\AmortizationService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\InsuranceContractEmailMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContratAssuranceCrudController extends AbstractCrudController
{
    public function __construct(
        private ContractGeneratorService $contractGenerator,
        private MailerInterface $mailer,
        private AmortizationService $amortizationService,
        private MessageBusInterface $messageBus,
        private TranslatorInterface $translator
    ) {}

    public static function getEntityFqcn(): string
    {
        return ContratAssurance::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Contrat d\'assurance')
            ->setEntityLabelInPlural('Contrats d\'assurance')
            ->setSearchFields(['numeroContrat', 'user.email', 'user.nom', 'user.prenom'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $activateAction = Action::new('activate', 'Activer', 'fa fa-play')
            ->linkToCrudAction('activate')
            ->displayIf(function (ContratAssurance $entity) {
                return $entity->getStatut() === ContratAssuranceStatusEnum::SUSPENDU;
            })
            ->setCssClass('btn btn-success');

        $suspendAction = Action::new('suspend', 'Suspendre', 'fa fa-pause')
            ->linkToCrudAction('suspend')
            ->displayIf(function (ContratAssurance $entity) {
                return $entity->getStatut() === ContratAssuranceStatusEnum::ACTIF;
            })
            ->setCssClass('btn btn-warning');

        $cancelAction = Action::new('cancel', 'Résilier', 'fa fa-stop')
            ->linkToCrudAction('cancel')
            ->displayIf(function (ContratAssurance $entity) {
                return in_array($entity->getStatut(), [ContratAssuranceStatusEnum::ACTIF, ContratAssuranceStatusEnum::SUSPENDU]);
            })
            ->setCssClass('btn btn-danger');

        $sendContractAction = Action::new('sendContract', 'Envoyer contrat', 'fa fa-envelope')
            ->linkToCrudAction('sendContract')
            ->displayIf(function (ContratAssurance $entity) {
                return $entity->getStatut() !== ContratAssuranceStatusEnum::RESILIE;
            })
            ->setCssClass('btn btn-info');

        return $actions
            ->add(Crud::PAGE_INDEX, $activateAction)
            ->add(Crud::PAGE_INDEX, $suspendAction)
            ->add(Crud::PAGE_INDEX, $cancelAction)
            ->add(Crud::PAGE_INDEX, $sendContractAction)
            ->add(Crud::PAGE_DETAIL, $activateAction)
            ->add(Crud::PAGE_DETAIL, $suspendAction)
            ->add(Crud::PAGE_DETAIL, $cancelAction)
            ->add(Crud::PAGE_DETAIL, $sendContractAction);
    }

    public function configureFields(string $pageName): iterable
    {
        // Calculer la date d'expiration pour l'utilisateur actuel si possible
        $calculatedExpirationDate = null;
        
        return [
            IdField::new('id')->hideOnForm()->hideOnIndex(),
            TextField::new('numeroContrat', 'Numéro de contrat')
                ->setDisabled()
                ->hideOnForm(),
            AssociationField::new('user', 'Utilisateur')
                ->setDisabled()
                ->formatValue(function ($value) {
                    if ($value instanceof \App\Entity\User) {
                        return $value->getFirstName() . ' ' . $value->getLastName() . ' (' . $value->getEmail() . ')';
                    }
                    return $value;
                }),
            AssociationField::new('demandeDevis', 'Demande de devis')
                ->setDisabled()
                ->formatValue(function ($value) {
                    if ($value instanceof \App\Entity\DemandeDevis) {
                        return $value->getNumeroDevis() . ' - ' . $value->getNomComplet();
                    }
                    return $value;
                })
                ->setHelp('La demande de devis est associée automatiquement et ne peut pas être modifiée')->hideOnIndex(),
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
                })
                ->hideOnForm(), // Masqué car récupéré automatiquement de la demande de devis
            ChoiceField::new('statut', 'Statut')
                ->setChoices([
                    'Actif' => ContratAssuranceStatusEnum::ACTIF,
                    'Suspendu' => ContratAssuranceStatusEnum::SUSPENDU,
                    'Résilié' => ContratAssuranceStatusEnum::RESILIE,
                    'Expiré' => ContratAssuranceStatusEnum::EXPIRE
                ])
                ->renderAsBadges([
                    ContratAssuranceStatusEnum::ACTIF->value => 'success',
                    ContratAssuranceStatusEnum::SUSPENDU->value => 'warning',
                    ContratAssuranceStatusEnum::RESILIE->value => 'danger',
                    ContratAssuranceStatusEnum::EXPIRE->value => 'secondary'
                ])
                ->formatValue(function ($value) {
                    return $value instanceof ContratAssuranceStatusEnum ? $value->getLabel() : $value;
                })
                ->hideOnForm(), // Masqué car défini automatiquement à ACTIF lors de la création
            NumberField::new('primeMensuelle', 'Prime mensuelle (€)')
                ->setNumDecimals(2),
            NumberField::new('montantCouverture', 'Montant de couverture (€)')
                ->setNumDecimals(2)
                ->setHelp('Montant de la couverture d\'assurance (défaut: 1 000 000 €)')
                ->setFormTypeOption('data', 1000000)
                ->hideOnIndex(),
            NumberField::new('fraisAssurance', 'Frais d\'assurance (€)')
                ->setNumDecimals(2)
                ->setHelp('Frais supplémentaires liés à l\'assurance')
                ->hideOnIndex(),
            NumberField::new('fraisDossier', 'Frais de dossier (€)')
                ->setNumDecimals(2)
                ->setHelp('Frais administratifs de traitement du dossier')
                ->hideOnIndex(),
            DateField::new('dateActivation', 'Date d\'activation')->hideOnIndex(),
            DateField::new('dateExpiration', 'Date d\'expiration')
                ->setHelp('Date calculée automatiquement basée sur le dernier prélèvement du crédit associé')
                ->setDisabled()->hideOnIndex(),
            DateField::new('dateResiliation', 'Date de résiliation')
                ->hideOnForm()
                ->hideOnIndex(),
            TextareaField::new('conditionsParticulieres', 'Conditions particulières')
                ->hideOnIndex(),
            TextareaField::new('noteAdmin', 'Note administrative')
                ->hideOnIndex(),
            DateTimeField::new('createdAt', 'Date de création')
                ->setDisabled()
                ->hideOnForm()
                ->hideOnIndex(),
            DateTimeField::new('updatedAt', 'Dernière mise à jour')
                ->hideOnForm()
                ->hideOnIndex(),
        ];
    }

    public function activate(AdminContext $context)
    {
        /** @var ContratAssurance $contrat */
        $contrat = $context->getEntity()->getInstance();
        
        try {
            $contrat->setStatut(ContratAssuranceStatusEnum::ACTIF);
            $this->container->get('doctrine')->getManagerForClass(ContratAssurance::class)->flush();
            $this->addFlash('success', 'Le contrat d\'assurance a été activé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'activation : ' . $e->getMessage());
        }
        
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
            
        return $this->redirect($url);
    }

    public function suspend(AdminContext $context)
    {
        /** @var ContratAssurance $contrat */
        $contrat = $context->getEntity()->getInstance();
        
        try {
            $contrat->setStatut(ContratAssuranceStatusEnum::SUSPENDU);
            $this->container->get('doctrine')->getManagerForClass(ContratAssurance::class)->flush();
            $this->addFlash('warning', 'Le contrat d\'assurance a été suspendu.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la suspension : ' . $e->getMessage());
        }
        
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
            
        return $this->redirect($url);
    }

    public function cancel(AdminContext $context)
    {
        /** @var ContratAssurance $contrat */
        $contrat = $context->getEntity()->getInstance();
        
        try {
            $contrat->setStatut(ContratAssuranceStatusEnum::RESILIE);
            $contrat->setDateResiliation(new \DateTimeImmutable());
            $this->container->get('doctrine')->getManagerForClass(ContratAssurance::class)->flush();
            $this->addFlash('success', 'Le contrat d\'assurance a été résilié.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la résiliation : ' . $e->getMessage());
        }
        
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
            
        return $this->redirect($url);
    }

    public function sendContract(AdminContext $context)
    {
        /** @var ContratAssurance $contrat */
        $contrat = $context->getEntity()->getInstance();
        
        try {
            // Déterminer la langue du client (par défaut français)
            $clientLocale = $contrat->getUser()->getLanguage() ?? 'fr';
            
            // Générer le PDF du contrat avec la locale du client
            $contractPdf = $this->contractGenerator->generateInsuranceContract($contrat, $clientLocale);
            $filename = $this->contractGenerator->getInsuranceContractFilename($contrat);
            
            // Traduire le type d'assurance et le statut
            $translatedInsuranceType = $this->translator->trans(
                $contrat->getTypeAssurance()->getLabel(), 
                [], 
                'enums', 
                $clientLocale
            );
            $translatedStatus = $this->translator->trans(
                $contrat->getStatut()->getLabel(), 
                [], 
                'enums', 
                $clientLocale
            );
            
            // Créer le message pour l'envoi asynchrone
            $message = new InsuranceContractEmailMessage(
                contratAssuranceId: $contrat->getId(),
                customerEmail: $contrat->getUser()->getEmail(),
                customerName: $contrat->getUser()->getFirstName() . ' ' . $contrat->getUser()->getLastName(),
                contractPdf: $contractPdf,
                contractFilename: $filename,
                contractNumber: $contrat->getNumeroContrat(),
                insuranceType: $translatedInsuranceType,
                insuranceTypeKey: $contrat->getTypeAssurance()->getLabel(), // Clé de traduction non traduite
                monthlyPremium: (float)$contrat->getPrimeMensuelle(),
                activationDate: $contrat->getDateActivation()->format('d/m/Y'),
                status: $translatedStatus,
                preferredLocale: $clientLocale
            );
            
            // Envoyer le message via le bus de messages
            $this->messageBus->dispatch($message);
            
            $this->addFlash('success', 'Le contrat sera envoyé par email à ' . $contrat->getUser()->getEmail());
            
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la préparation de l\'envoi du contrat : ' . $e->getMessage());
        }
        
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
            
        return $this->redirect($url);
    }

    public function createEntity(string $entityFqcn)
    {
        $contrat = new ContratAssurance();
        
        // Définir le statut par défaut
        $contrat->setStatut(ContratAssuranceStatusEnum::ACTIF);
        
        // Définir la date d'activation par défaut à aujourd'hui
        $contrat->setDateActivation(new \DateTimeImmutable());
        
        // Essayer de calculer la date d'expiration pour les nouveaux contrats
        // (Sera mis à jour quand l'utilisateur sera assigné)
        
        return $contrat;
    }

    public function configureAdmin(AdminContext $context): void
    {
        $entity = $context->getEntity();
        if ($entity && $entity->getInstance() instanceof ContratAssurance) {
            /** @var ContratAssurance $contrat */
            $contrat = $entity->getInstance();
            
            // Calculer la date d'expiration si l'utilisateur est défini
            if ($contrat->getUser()) {
                $expirationDate = $this->calculateExpirationDateFromCredit($contrat->getUser());
                if ($expirationDate && !$contrat->getDateExpiration()) {
                    $contrat->setDateExpiration($expirationDate);
                }
            }
        }
    }

    public function configureResponseParameters(\EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore $responseParameters): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore
    {
        // Pré-calculer la date d'expiration pour l'affichage dans le formulaire
        if (in_array($responseParameters->get('pageName'), ['edit', 'new'])) {
            $entity = $responseParameters->get('entity');
            if ($entity && $entity->getInstance() instanceof ContratAssurance) {
                /** @var ContratAssurance $contrat */
                $contrat = $entity->getInstance();
                
                // Pour les nouveaux contrats, essayer de récupérer l'utilisateur depuis la demande de devis
                if (!$contrat->getUser() && $contrat->getDemandeDevis()) {
                    $entityManager = $this->container->get('doctrine')->getManagerForClass(ContratAssurance::class);
                    $userRepository = $entityManager->getRepository(\App\Entity\User::class);
                    $user = $userRepository->findOneBy(['email' => $contrat->getDemandeDevis()->getEmail()]);
                    if ($user) {
                        $contrat->setUser($user);
                    }
                }
                
                // TOUJOURS recalculer et définir la date d'expiration
                if ($contrat->getUser()) {
                    $expirationDate = $this->calculateExpirationDateFromCredit($contrat->getUser());
                    if ($expirationDate) {
                        $contrat->setDateExpiration($expirationDate);
                        
                        // Forcer la mise à jour de l'entité dans le contexte EasyAdmin
                        $responseParameters->set('entity', $entity);
                    }
                }
            }
        }
        
        return $responseParameters;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var ContratAssurance $contrat */
        $contrat = $entityInstance;
        
        // Si une demande de devis est associée, récupérer automatiquement les informations
        if ($contrat->getDemandeDevis()) {
            $demandeDevis = $contrat->getDemandeDevis();
            
            // Récupérer le type d'assurance de la demande de devis
            if (!$contrat->getTypeAssurance() && $demandeDevis->getTypeAssurance()) {
                $contrat->setTypeAssurance($demandeDevis->getTypeAssurance());
            }
            
            // Récupérer l'utilisateur de la demande de devis si pas déjà défini
            if (!$contrat->getUser()) {
                // Chercher l'utilisateur par email de la demande de devis
                $userRepository = $entityManager->getRepository(\App\Entity\User::class);
                $user = $userRepository->findOneBy(['email' => $demandeDevis->getEmail()]);
                if ($user) {
                    $contrat->setUser($user);
                }
            }
        }
        
        // Calculer automatiquement la date d'expiration basée sur le crédit associé
        if ($contrat->getUser() && !$contrat->getDateExpiration()) {
            $expirationDate = $this->calculateExpirationDateFromCredit($contrat->getUser());
            if ($expirationDate) {
                $contrat->setDateExpiration($expirationDate);
            }
        }
        
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var ContratAssurance $contrat */
        $contrat = $entityInstance;
        
        // Recalculer la date d'expiration basée sur le crédit associé
        if ($contrat->getUser()) {
            $expirationDate = $this->calculateExpirationDateFromCredit($contrat->getUser());
            if ($expirationDate) {
                $contrat->setDateExpiration($expirationDate);
            }
        }
        
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function calculateExpirationDateFromCredit(\App\Entity\User $user): ?\DateTimeImmutable
    {
        // Chercher la demande de crédit la plus récente pour cet utilisateur
        $entityManager = $this->container->get('doctrine')->getManagerForClass(ContratAssurance::class);
        $creditApplicationRepo = $entityManager->getRepository(\App\Entity\CreditApplication::class);
        
        $creditApplication = $creditApplicationRepo->createQueryBuilder('ca')
            ->where('ca.email = :email')
            ->andWhere('ca.status IN (:validStatuses)')
            ->setParameter('email', $user->getEmail())
            ->setParameter('validStatuses', ['pending', 'approved', 'contract_validated', 'disbursed', 'in_progress'])
            ->orderBy('ca.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$creditApplication) {
            return null;
        }

        // Utiliser le service d'amortissement existant pour récupérer le tableau
        if ($this->amortizationService->hasAmortizationSchedule($creditApplication)) {
            $amortizationTable = $this->amortizationService->getAmortizationTableFromDatabase($creditApplication);
            
            if (!empty($amortizationTable)) {
                // Récupérer le dernier élément du tableau (dernière échéance)
                $lastPayment = end($amortizationTable);
                
                if ($lastPayment && isset($lastPayment['date'])) {
                    $paymentDate = $lastPayment['date'];
                    if ($paymentDate instanceof \DateTime) {
                        return \DateTimeImmutable::createFromMutable($paymentDate);
                    } elseif ($paymentDate instanceof \DateTimeImmutable) {
                        return $paymentDate;
                    }
                }
            }
        }

        return null;
    }
}