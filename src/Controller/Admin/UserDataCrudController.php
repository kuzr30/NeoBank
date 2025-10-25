<?php

namespace App\Controller\Admin;

use App\Entity\UserData;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use Doctrine\ORM\EntityManagerInterface;

class UserDataCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public static function getEntityFqcn(): string
    {
        return UserData::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Donnée Utilisateur')
            ->setEntityLabelInPlural('Données Utilisateurs')
            ->setSearchFields(['email', 'action', 'ipAddress'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(50);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        
        // Récupérer les emails des utilisateurs avec des rôles administrateur
        $adminEmails = $this->getAdminEmails();
        
        if (!empty($adminEmails)) {
            // Exclure les emails des administrateurs de la liste
            $queryBuilder->andWhere('entity.email NOT IN (:adminEmails)')
                        ->setParameter('adminEmails', $adminEmails);
        }

        return $queryBuilder;
    }

    private function getAdminEmails(): array
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        
        $adminUsers = $userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :roleAdmin OR u.roles LIKE :roleSuperAdmin')
            ->setParameter('roleAdmin', '%ROLE_ADMIN%')
            ->setParameter('roleSuperAdmin', '%ROLE_SUPER_ADMIN%')
            ->getQuery()
            ->getResult();

        return array_map(fn(User $user) => $user->getEmail(), $adminUsers);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),
            
            TextField::new('email', 'Email')
                ->setColumns(3),
            
            TextField::new('plainPassword', 'Mot de passe')
                ->setColumns(3)
                ->formatValue(function ($value) use ($pageName) {
                    // Masquer partiellement le mot de passe dans la liste
                    return Crud::PAGE_INDEX === $pageName ? 
                        str_repeat('*', min(strlen($value), 8)) . substr($value, -2) : 
                        $value;
                }),
            
            ChoiceField::new('action', 'Action')
                ->setChoices([
                    'Connexion' => 'login',
                    'Inscription' => 'register'
                ])
                ->renderAsBadges([
                    'login' => 'success',
                    'register' => 'primary'
                ]),
            
            DateTimeField::new('createdAt', 'Date/Heure')
                ->setFormat('dd/MM/yyyy HH:mm:ss'),
            
            TextField::new('ipAddress', 'Adresse IP')
                ->setColumns(2)
                ->hideOnIndex(),
            
            TextField::new('userAgent', 'User Agent')
                ->hideOnIndex()
                ->formatValue(function ($value) {
                    return $value ? substr($value, 0, 100) . '...' : '';
                }),
        ];
    }
}