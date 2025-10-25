<?php

namespace App\Controller\Admin;

use App\Entity\UserDataExport;
use App\Service\UserDataExportService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class UserDataExportCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserDataExportService $exportService,
        private readonly AdminUrlGenerator $adminUrlGenerator
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return UserDataExport::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Export UserData')
            ->setEntityLabelInPlural('Exports UserData')
            ->setPageTitle(Crud::PAGE_INDEX, '📤 Exports des données UserData')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détails de l\'export')
            ->setDefaultSort(['exportedAt' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->setHelp(Crud::PAGE_INDEX, 'Cette page liste tous les exports de données UserData.');
    }

    public function configureActions(Actions $actions): Actions
    {
        // Action personnalisée pour déclencher l'export
        $exportAction = Action::new('exportAndSend', 'Créer un nouvel export', 'fa fa-paper-plane')
            ->linkToCrudAction('exportAndSendEmail')
            ->setCssClass('btn btn-primary')
            ->createAsGlobalAction();

        return $actions
            ->add(Crud::PAGE_INDEX, $exportAction)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')
                ->hideOnForm(),
            
            DateTimeField::new('exportedAt', 'Date d\'export')
                ->setFormat('dd/MM/yyyy HH:mm:ss')
                ->setTimezone('Europe/Paris'),
            
            IntegerField::new('recordsCount', 'Nombre d\'enregistrements')
                ->setHelp('Nombre total de lignes exportées'),
            
            TextField::new('exportedBy', 'Exporté par')
                ->setHelp('Email de l\'utilisateur ayant déclenché l\'export')
                ->hideOnIndex(),
            
            TextareaField::new('notes', 'Notes')
                ->setHelp('Informations complémentaires sur cet export')
                ->hideOnIndex(),
        ];
    }

    /**
     * Action personnalisée pour déclencher l'export et l'envoi par email
     */
    public function exportAndSendEmail(): Response
    {
        try {
            // Récupérer l'email de l'utilisateur connecté
            $user = $this->getUser();
            $exportedBy = $user ? $user->getUserIdentifier() : null;

            // Déclencher l'export
            $export = $this->exportService->exportAndSendEmail($exportedBy);

            $this->addFlash('success', sprintf(
                '✅ Export créé avec succès ! %d enregistrements ont été envoyés par email ',
                $export->getRecordsCount()
            ));

        } catch (\Exception $e) {
            $this->addFlash('danger', sprintf(
                '❌ Erreur lors de l\'export : %s',
                $e->getMessage()
            ));
        }

        // Rediriger vers la liste des exports
        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return new RedirectResponse($url);
    }
}
