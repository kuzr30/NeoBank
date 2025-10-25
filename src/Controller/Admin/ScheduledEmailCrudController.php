<?php

namespace App\Controller\Admin;

use App\Entity\ScheduledEmail;
use App\Entity\User;
use App\Enum\AccountIncompleteReason;
use App\Enum\CreditApplicationIncompleteReason;
use App\Enum\EmailStatus;
use App\Enum\EmailTemplateType;
use App\Enum\KycRejectionReason;
use App\Service\ScheduledEmailSender;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ScheduledEmailCrudController extends AbstractCrudController
{
    public function __construct(
        private ScheduledEmailSender $emailSender,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ScheduledEmail::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Email')
            ->setEntityLabelInPlural('Emails envoyés')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['recipient.email', 'recipient.firstname', 'recipient.lastname'])
            ->setPageTitle('index', 'Gestion des emails')
            ->setPageTitle('detail', 'Détails de l\'email');
    }

    public function configureActions(Actions $actions): Actions
    {
        $sendEmail = Action::new('sendEmail', 'Envoyer un email', 'fa fa-envelope')
            ->linkToRoute('admin_send_scheduled_email')
            ->createAsGlobalAction()
            ->setCssClass('btn btn-primary');

        $resend = Action::new('resend', 'Renvoyer', 'fa fa-redo')
            ->linkToCrudAction('resend')
            ->displayIf(static function (ScheduledEmail $email) {
                return $email->getStatus() === EmailStatus::FAILED || $email->getStatus() === EmailStatus::SENT;
            })
            ->setCssClass('btn btn-warning');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $sendEmail)
            ->add(Crud::PAGE_DETAIL, $resend)
            ->add(Crud::PAGE_EDIT, Action::DETAIL)
            ->disable(Crud::PAGE_NEW)
            ->disable(Crud::PAGE_EDIT)
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->displayIf(static function (ScheduledEmail $email) {
                    return $email->getStatus() !== EmailStatus::SENT;
                });
            });
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()->hideOnIndex();

       /*  yield ChoiceField::new('templateType', 'Type de template')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => EmailTemplateType::class,
                'choice_label' => fn($choice) => $choice->getLabel(),
            ])
            ->setRequired(true)
            ->onlyOnForms()->hideOnForm()->hideOnIndex(); */
        
        yield TextField::new('templateTypeLabel', 'Type de template')
            ->setVirtual(true)
            ->formatValue(fn($value, ScheduledEmail $email) => $email->getTemplateType()->getLabel())
            ->hideOnForm()->hideOnIndex();

        yield AssociationField::new('recipient', 'Destinataire')
            ->setRequired(true)
            ->setFormTypeOptions([
                'choice_label' => function (User $user) {
                    return sprintf('%s %s (%s)', 
                        $user->getFirstname() ?? '', 
                        $user->getLastname() ?? '', 
                        $user->getEmail()
                    );
                },
            ]);

        yield ChoiceField::new('locale', 'Langue')
            ->setChoices([
                'Français' => 'fr',
                'English' => 'en',
                'Nederlands' => 'nl',
                'Deutsch' => 'de',
                'Español' => 'es',
            ])
            ->setRequired(true)
            ->formatValue(fn($value) => match($value) {
                'fr' => 'Français',
                'en' => 'English',
                'nl' => 'Nederlands',
                'de' => 'Deutsch',
                'es' => 'Español',
                default => $value,
            });

        // Combined reasons field with all possible reasons
        $allReasons = [];
        foreach (KycRejectionReason::cases() as $reason) {
            $allReasons[$reason->getLabel()] = $reason->value;
        }
        foreach (AccountIncompleteReason::cases() as $reason) {
            $allReasons[$reason->getLabel()] = $reason->value;
        }
        foreach (CreditApplicationIncompleteReason::cases() as $reason) {
            $allReasons[$reason->getLabel()] = $reason->value;
        }

        yield ChoiceField::new('reasons', 'Raisons')
            ->setChoices($allReasons)
            ->allowMultipleChoices()
            ->renderExpanded()
            ->setHelp('Sélectionnez les raisons (affichage automatique selon le type de template)')
            ->onlyOnForms();

        yield MoneyField::new('amount', 'Montant')
            ->setCurrency('EUR')
            ->setHelp('Montant à afficher dans l\'email (uniquement pour le template "Coordonnées bancaires")')
            ->hideOnIndex();

        yield TextareaField::new('customMessage', 'Message personnalisé')
            ->setHelp('Message additionnel qui sera affiché dans l\'email')
            ->hideOnIndex();

        $statusField = TextField::new('statusLabel', 'Statut')
            ->setVirtual(true)
            ->formatValue(function ($value, ScheduledEmail $email) {
                $status = $email->getStatus();
                $badgeClass = match($status) {
                    EmailStatus::PENDING => 'warning',
                    EmailStatus::SENT => 'success',
                    EmailStatus::FAILED => 'danger',
                    EmailStatus::SCHEDULED => 'info',
                };
                return sprintf('<span class="badge bg-%s">%s</span>', $badgeClass, $status->getLabel());
            });
        
        yield $statusField;

        yield DateTimeField::new('sentAt', 'Envoyé le')
            ->hideOnIndex();

        yield TextareaField::new('errorMessage', 'Message d\'erreur')
            ->hideOnIndex()
            ->formatValue(function ($value) {
                return $value ?: '';
            });

        yield AssociationField::new('createdBy', 'Créé par')->hideOnForm()->hideOnIndex();

        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm()->hideOnIndex();

        yield DateTimeField::new('updatedAt', 'Mis à jour le')
            ->hideOnIndex();
    }

    public function createEntity(string $entityFqcn)
    {
        $scheduledEmail = new ScheduledEmail();
        $scheduledEmail->setLocale('fr');
        $scheduledEmail->setStatus(EmailStatus::PENDING);

        // Set the current admin user as creator
        if ($this->getUser()) {
            $scheduledEmail->setCreatedBy($this->getUser());
        }

        return $scheduledEmail;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof ScheduledEmail) {
            return;
        }

        // Always set as PENDING for immediate send
        $entityInstance->setStatus(EmailStatus::PENDING);
        $entityInstance->setScheduledFor(null);

        parent::persistEntity($entityManager, $entityInstance);

        // Dispatch message for immediate send
        $this->messageBus->dispatch(new SendScheduledEmailMessage($entityInstance->getId()));
        $this->addFlash('success', 'Email créé et ajouté à la file d\'envoi.');
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof ScheduledEmail) {
            return;
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function resend(EntityManagerInterface $entityManager): RedirectResponse
    {
        $scheduledEmail = $this->getContext()->getEntity()->getInstance();

        if (!$scheduledEmail instanceof ScheduledEmail) {
            $this->addFlash('error', 'Email invalide.');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        // Reset status and error message
        $scheduledEmail->setStatus(EmailStatus::PENDING);
        $scheduledEmail->setErrorMessage(null);
        $scheduledEmail->setScheduledFor(null);
        $entityManager->flush();

        // Send email immediately
        $sent = $this->emailSender->send($scheduledEmail);
        
        if ($sent) {
            $this->addFlash('success', 'Email renvoyé avec succès à ' . $scheduledEmail->getRecipient()->getEmail());
        } else {
            $this->addFlash('error', 'Erreur lors du renvoi de l\'email. Consultez les logs pour plus de détails.');
        }

        return $this->redirectToRoute('app_admin_dashboard', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class,
        ]);
    }
}
