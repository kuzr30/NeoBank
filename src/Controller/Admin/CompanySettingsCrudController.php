<?php

namespace App\Controller\Admin;

use App\Entity\CompanySettings;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class CompanySettingsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CompanySettings::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('companyName', 'Nom de l\'entreprise'),
            TextareaField::new('address', 'Adresse'),
            TextField::new('phone', 'Téléphone'),
            TextField::new('email', 'Email'),
            TextField::new('website', 'Site web')->setRequired(false),
            TextField::new('siret', 'SIRET')->setRequired(false),
            TextareaField::new('logoBase64', 'Logo (Base64)')->setRequired(false),
            TextareaField::new('legalMention', 'Mention légale')->setRequired(false),
            DateTimeField::new('updatedAt', 'Dernière modification')->setFormTypeOption('disabled', true)
        ];
    }
}
