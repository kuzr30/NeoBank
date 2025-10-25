<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;

class UserCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setPageTitle('index', 'Gestion des utilisateurs')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['email', 'firstName', 'lastName'])
            ->setPaginatorPageSize(20);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        
        $currentUser = $this->getUser();
        
        // Si l'utilisateur connecté est un super admin, il voit tout le monde
        if ($currentUser && in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles())) {
            return $queryBuilder;
        }
        
        // Si l'utilisateur connecté est un admin (mais pas super admin), 
        // il ne peut pas voir les super admins
        if ($currentUser && in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            $queryBuilder->andWhere('entity.roles NOT LIKE :superAdminRole')
                        ->setParameter('superAdminRole', '%ROLE_SUPER_ADMIN%');
        }
        
        return $queryBuilder;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setIcon('fa fa-plus')->setLabel('Nouvel utilisateur');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-edit');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash');
            });
    }

    public function configureFilters(Filters $filters): Filters
    {
        $currentUser = $this->getUser();
        $roleChoices = [
            'Utilisateur' => 'ROLE_USER',
            'Client' => 'ROLE_CLIENT',
            'Administrateur' => 'ROLE_ADMIN',
        ];

        // Seuls les super admins peuvent voir le filtre super admin
        if ($currentUser && in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles())) {
            $roleChoices['Super Administrateur'] = 'ROLE_SUPER_ADMIN';
        }

        return $filters
            ->add(BooleanFilter::new('isVerified', 'Compte vérifié'))
            ->add(ChoiceFilter::new('roles', 'Rôle')->setChoices($roleChoices))
            ->add(DateTimeFilter::new('createdAt', 'Date d\'inscription'))
            ->add(DateTimeFilter::new('lastLoginAt', 'Dernière connexion'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        
        yield TextField::new('firstName', 'Prénom')
            ->setRequired(true);
            
        yield TextField::new('lastName', 'Nom')
            ->setRequired(true);
            
        yield EmailField::new('email', 'Email')
            ->setRequired(true);

         yield TextField::new('language', 'Langue')
            ->setRequired(true);


        $passwordField = TextField::new('password', 'Mot de passe')
            ->setFormType(PasswordType::class)
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->hideOnIndex()
            ->hideOnDetail()->hideOnForm();
        
        if ($pageName === Crud::PAGE_EDIT) {
            $passwordField->setHelp('Laissez vide pour ne pas modifier le mot de passe');
        }
        
        yield $passwordField;

        // Configuration des rôles selon les permissions de l'utilisateur connecté
        $currentUser = $this->getUser();
        $roleChoices = [
            'Utilisateur' => 'ROLE_USER',
            'Client' => 'ROLE_CLIENT',
        ];

        // Les admins peuvent attribuer le rôle admin
        if ($currentUser && in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            $roleChoices['Administrateur'] = 'ROLE_ADMIN';
        }

        // Seuls les super admins peuvent attribuer le rôle super admin
        if ($currentUser && in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles())) {
            $roleChoices['Super Administrateur'] = 'ROLE_SUPER_ADMIN';
        }

        yield ChoiceField::new('roles', 'Rôles')
            ->setChoices($roleChoices)
            ->allowMultipleChoices()
            ->renderExpanded(false)
            ->renderAsBadges([
                'ROLE_USER' => 'secondary',
                'ROLE_CLIENT' => 'primary',
                'ROLE_ADMIN' => 'warning',
                'ROLE_SUPER_ADMIN' => 'danger'
            ]);

        yield BooleanField::new('verified', 'Compte vérifié')
            ->renderAsSwitch(false);

        // Masquer temporairement les champs DateTime pour éviter les erreurs Intl
        // yield Field::new('createdAt', 'Date d\'inscription')
        //     ->setTemplatePath('admin/field/datetime.html.twig')
        //     ->hideOnForm();

        // yield Field::new('lastLoginAt', 'Dernière connexion')
        //     ->setTemplatePath('admin/field/datetime.html.twig')
        //     ->hideOnForm();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User && $entityInstance->getPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $entityInstance,
                $entityInstance->getPassword()
            );
            $entityInstance->setPassword($hashedPassword);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $currentUser = $this->getUser();
        
        // Protection : un admin ne peut pas modifier un super admin
        if ($entityInstance instanceof User && 
            in_array('ROLE_SUPER_ADMIN', $entityInstance->getRoles()) && 
            $currentUser && 
            !in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles())) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les droits pour modifier un super administrateur.');
        }

        if ($entityInstance instanceof User && $entityInstance->getPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $entityInstance,
                $entityInstance->getPassword()
            );
            $entityInstance->setPassword($hashedPassword);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $currentUser = $this->getUser();
        
        // Protection : un admin ne peut pas supprimer un super admin
        if ($entityInstance instanceof User && 
            in_array('ROLE_SUPER_ADMIN', $entityInstance->getRoles()) && 
            $currentUser && 
            !in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles())) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les droits pour supprimer un super administrateur.');
        }

        // Protection : empêcher la suppression du dernier super admin
        if ($entityInstance instanceof User && in_array('ROLE_SUPER_ADMIN', $entityInstance->getRoles())) {
            $superAdminCount = $entityManager->getRepository(User::class)
                ->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_SUPER_ADMIN%')
                ->getQuery()
                ->getSingleScalarResult();
                
            if ($superAdminCount <= 1) {
                throw $this->createAccessDeniedException('Impossible de supprimer le dernier super administrateur du système.');
            }
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
