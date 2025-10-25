<?php

namespace App\Controller\Admin;

use App\Entity\CustomEmail;
use App\Entity\User;
use App\Message\SendCustomEmailMessage;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Doctrine\ORM\EntityManagerInterface;

class CustomEmailCrudController extends AbstractCrudController
{
    private string $uploadsDirectory;

    public function __construct(
        private MessageBusInterface $messageBus,
        private EntityManagerInterface $entityManager,
        private AdminUrlGenerator $adminUrlGenerator,
        string $uploadsDirectory,
    ) {
        $this->uploadsDirectory = $uploadsDirectory;
    }

    public static function getEntityFqcn(): string
    {
        return CustomEmail::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Email personnalisé')
            ->setEntityLabelInPlural('Emails personnalisés')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['recipient.email', 'recipient.firstname', 'recipient.lastname', 'subject']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $sendAction = Action::new('send', 'Envoyer', 'fa fa-paper-plane')
            ->linkToCrudAction('sendEmail')
            ->displayIf(static function (CustomEmail $email) {
                return $email->getStatus() === 'pending';
            });

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $sendAction)
            ->disable(Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        
        yield AssociationField::new('recipient', 'Destinataire')
            ->setRequired(true)
            ->autocomplete();

        yield ChoiceField::new('locale', 'Langue')
            ->setChoices([
                'Français' => 'fr',
                'English' => 'en',
                'Nederlands' => 'nl',
                'Español' => 'es',
                'Deutsch' => 'de',
            ])
            ->setRequired(true)
            ->setHelp('Langue utilisée pour les éléments du template (pièces jointes, footer, etc.)');

        yield TextField::new('subject', 'Sujet')
            ->setRequired(true)
            ->setMaxLength(255);

        yield TextEditorField::new('message', 'Message')
            ->setRequired(true)
            ->hideOnIndex()
            ->setHelp('Utilisez l\'éditeur pour formater votre message (gras, italique, listes, liens, etc.)');

        yield Field::new('attachmentFiles', 'Pièces jointes')
            ->setFormType(\Symfony\Component\Form\Extension\Core\Type\FileType::class)
            ->setFormTypeOptions([
                'multiple' => true,
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'accept' => '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.zip',
                ],
            ])
            ->onlyOnForms()
            ->setHelp('Formats acceptés : PDF, DOC, DOCX, XLS, XLSX, images, ZIP (max 10 Mo par fichier)');

        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'En attente' => 'pending',
                'Envoyé' => 'sent',
                'Erreur' => 'failed',
            ])
            ->hideOnForm();

        yield AssociationField::new('createdBy', 'Créé par')
            ->hideOnForm()->hideOnIndex();

        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm()->hideOnIndex();

        yield DateTimeField::new('sentAt', 'Envoyé le')
            ->hideOnForm()->hideOnIndex();

        yield TextareaField::new('errorMessage', 'Message d\'erreur')
            ->hideOnForm()
            ->hideOnIndex();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof CustomEmail) {
            $entityInstance->setCreatedBy($this->getUser());
            $entityInstance->setStatus('pending');
            
            // Gérer les fichiers uploadés
            $form = $this->getContext()->getCrud()->getCurrentAction() === Action::NEW 
                ? $this->getContext()->getRequest()->files->get('CustomEmail')
                : null;
            
            if ($form && isset($form['attachmentFiles'])) {
                $uploadedFiles = $form['attachmentFiles'];
                $attachmentPaths = [];
                
                foreach ($uploadedFiles as $uploadedFile) {
                    if ($uploadedFile instanceof UploadedFile && $uploadedFile->isValid()) {
                        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                        $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();
                        
                        $uploadedFile->move($this->uploadsDirectory, $newFilename);
                        $attachmentPaths[] = $newFilename;
                    }
                }
                
                if (!empty($attachmentPaths)) {
                    $entityInstance->setAttachments($attachmentPaths);
                }
            }
        }
        
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function sendEmail(AdminContext $context): Response
    {
        $customEmail = $context->getEntity()->getInstance();

        if (!$customEmail instanceof CustomEmail) {
            throw new \LogicException('Entity must be an instance of CustomEmail');
        }

        if ($customEmail->getStatus() !== 'pending') {
            $this->addFlash('danger', 'Cet email a déjà été envoyé.');
            
            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl();
            
            return $this->redirect($url);
        }

        // Envoyer le message au bus de messages pour traitement asynchrone
        $this->messageBus->dispatch(new SendCustomEmailMessage($customEmail->getId()));

        $this->addFlash('success', sprintf(
            'Email mis en file d\'attente pour envoi à %s (%s)',
            $customEmail->getRecipient()->getFirstname() . ' ' . $customEmail->getRecipient()->getLastname(),
            $customEmail->getRecipient()->getEmail()
        ));

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }
}
