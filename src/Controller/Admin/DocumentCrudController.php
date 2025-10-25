<?php

namespace App\Controller\Admin;

use App\Entity\Document;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;

class DocumentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Document::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name', 'Nom du document'),
            TextField::new('originalName', 'Nom original')->hideOnIndex(),
            AssociationField::new('owner', 'Propriétaire'),
            AssociationField::new('verifiedBy', 'Vérifié par')->hideOnIndex(),
            AssociationField::new('creditApplication', 'Demande de crédit')->hideOnIndex(),
            TextField::new('filePath', 'Chemin du fichier')->hideOnIndex(),
            TextField::new('mimeType', 'Type MIME')->hideOnIndex(),
            NumberField::new('fileSize', 'Taille du fichier')->hideOnIndex(),
            TextField::new('fileHash', 'Hash du fichier')->hideOnIndex(),
            ChoiceField::new('type', 'Type')
                ->setChoices([
                    'Pièce d\'identité' => 'identity',
                    'Justificatif de revenus' => 'income_proof',
                    'Justificatif de domicile' => 'address_proof',
                    'Relevé bancaire' => 'bank_statement',
                    'Contrat de travail' => 'employment_contract',
                    'Bulletin de salaire' => 'payslip',
                    'Déclaration d\'impôts' => 'tax_return',
                    'Passeport' => 'passport',
                    'Permis de conduire' => 'driving_license',
                    'Facture' => 'bill',
                    'Autres' => 'other',
                ]),
            ChoiceField::new('category', 'Catégorie')
                ->setChoices([
                    'KYC' => 'kyc',
                    'Financier' => 'financial',
                    'Légal' => 'legal',
                    'Personnel' => 'personal',
                ]),
            ChoiceField::new('verificationStatus', 'Statut de vérification')
                ->setChoices([
                    'En attente' => 'pending',
                    'Vérifié' => 'verified',
                    'Rejeté' => 'rejected',
                ]),
            ChoiceField::new('securityClassification', 'Classification de sécurité')
                ->setChoices([
                    'Public' => 'public',
                    'Interne' => 'internal',
                    'Confidentiel' => 'confidential',
                    'Restreint' => 'restricted',
                ]),
            TextareaField::new('description', 'Description')->hideOnIndex(),
            TextareaField::new('rejectionReason', 'Raison du rejet')->hideOnIndex(),
            BooleanField::new('isConfidential', 'Confidentiel'),
            BooleanField::new('isArchived', 'Archivé'),
            DateTimeField::new('expiryDate', 'Date d\'expiration')->hideOnIndex(),
            DateTimeField::new('verifiedAt', 'Vérifié le')->hideOnForm(),
            DateTimeField::new('createdAt', 'Créé le')->hideOnForm(),
            DateTimeField::new('updatedAt', 'Modifié le')->hideOnForm(),
            ArrayField::new('metadata', 'Métadonnées')->hideOnIndex(),
        ];
    }
}
