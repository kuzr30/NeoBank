<?php

namespace App\Command;

use App\Entity\CompanySettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-company-data',
    description: 'Fix company data by removing % symbols and ensuring clean values',
)]
class FixCompanyDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Vérification et correction des données CompanySettings');
        
        $repository = $this->entityManager->getRepository(CompanySettings::class);
        $settings = $repository->findAll();
        
        if (empty($settings)) {
            $io->warning('Aucune donnée CompanySettings trouvée en base.');
            $io->text('Création d\'un nouvel enregistrement...');
            
            $newSettings = new CompanySettings();
            $newSettings->setCompanyName('SEDEF BANK');
            $newSettings->setPhone('02 22 55 66 00');
            $newSettings->setEmail('credit@sedef.fr');
            $newSettings->setAddress('3 Mail du Commandant Cousteau, 91300 Massy');
            $newSettings->setWebsite('www.sedef.fr');
            $newSettings->setSiret('12345678901234');
            $newSettings->setLegalMention('Services financiers de confiance ');
            
            $this->entityManager->persist($newSettings);
            $this->entityManager->flush();
            
            $io->success('Nouvelles données créées avec succès !');
            return Command::SUCCESS;
        }
        
        foreach ($settings as $setting) {
            $io->section("Données actuelles (ID: {$setting->getId()})");
            
            $io->table(
                ['Champ', 'Valeur actuelle'],
                [
                    ['Nom', $setting->getCompanyName()],
                    ['Téléphone', $setting->getPhone()],
                    ['Email', $setting->getEmail()],
                    ['Adresse', $setting->getAddress()],
                    ['Site web', $setting->getWebsite()],
                ]
            );
            
            $needUpdate = false;
            
            // Nettoyer le téléphone
            $phone = $setting->getPhone();
            if ($phone && (strpos($phone, '%') !== false || trim($phone) !== $phone)) {
                $cleanPhone = str_replace(['%', 'company_phone'], '', $phone);
                $cleanPhone = trim($cleanPhone) ?: '02 22 55 66 00';
                $setting->setPhone($cleanPhone);
                $io->text("🔧 Téléphone corrigé: '$phone' → '$cleanPhone'");
                $needUpdate = true;
            }
            
            // Nettoyer l'email
            $email = $setting->getEmail();
            if ($email && ($email !== 'credit@sedef.fr' || strpos($email, '%') !== false || trim($email) !== $email)) {
                $cleanEmail = str_replace(['%', 'company_email'], '', $email);
                $cleanEmail = trim($cleanEmail) ?: 'credit@sedef.fr';
                if ($cleanEmail !== 'credit@sedef.fr') {
                    $cleanEmail = 'credit@sedef.fr';
                }
                $setting->setEmail($cleanEmail);
                $io->text("🔧 Email corrigé: '$email' → '$cleanEmail'");
                $needUpdate = true;
            }
            
            // Nettoyer l'adresse
            $address = $setting->getAddress();
            if ($address && ($address !== '3 Mail du Commandant Cousteau, 91300 Massy' || strpos($address, '%') !== false || trim($address) !== $address)) {
                $cleanAddress = str_replace(['%', 'company_address'], '', $address);
                $cleanAddress = trim($cleanAddress) ?: '3 Mail du Commandant Cousteau, 91300 Massy';
                if ($cleanAddress !== '3 Mail du Commandant Cousteau, 91300 Massy') {
                    $cleanAddress = '3 Mail du Commandant Cousteau, 91300 Massy';
                }
                $setting->setAddress($cleanAddress);
                $io->text("🔧 Adresse corrigée: '$address' → '$cleanAddress'");
                $needUpdate = true;
            }
            
            // Nettoyer le site web
            $website = $setting->getWebsite();
            if ($website && ($website !== 'www.sedef.fr' || strpos($website, '%') !== false || trim($website) !== $website)) {
                $cleanWebsite = str_replace(['%', 'company_website'], '', $website);
                $cleanWebsite = trim($cleanWebsite) ?: 'www.sedef.fr';
                if ($cleanWebsite !== 'www.sedef.fr') {
                    $cleanWebsite = 'www.sedef.fr';
                }
                $setting->setWebsite($cleanWebsite);
                $io->text("🔧 Site web corrigé: '$website' → '$cleanWebsite'");
                $needUpdate = true;
            }
            
            // S'assurer que le nom est en majuscules
            if ($setting->getCompanyName() !== 'SEDEF BANK') {
                $oldName = $setting->getCompanyName();
                $setting->setCompanyName('SEDEF BANK');
                $io->text("🔧 Nom corrigé: '$oldName' → 'SEDEF BANK'");
                $needUpdate = true;
            }
            
            if ($needUpdate) {
                $setting->setUpdatedAt(new \DateTimeImmutable());
                $this->entityManager->flush();
                $io->success('Données mises à jour avec succès !');
            } else {
                $io->info('Aucune correction nécessaire.');
            }
            
            $io->table(
                ['Champ', 'Valeur finale'],
                [
                    ['Nom', $setting->getCompanyName()],
                    ['Téléphone', $setting->getPhone()],
                    ['Email', $setting->getEmail()],
                    ['Adresse', $setting->getAddress()],
                    ['Site web', $setting->getWebsite()],
                ]
            );
        }
        
        $io->success('Vérification terminée !');
        return Command::SUCCESS;
    }
}