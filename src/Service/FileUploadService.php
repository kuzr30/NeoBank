<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploadService
{
    public function __construct(
        private SluggerInterface $slugger,
        private string $projectDir,
        private ProfessionalTranslationService $translationService
    ) {}

    /**
     * Upload d'un fichier de contrat signé
     */
    public function uploadSignedContract(UploadedFile $file, string $contractNumber): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $contractNumber . '_signed_' . $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $uploadDir = $this->projectDir . '/var/signed_contracts';
        $this->ensureDirectoryExists($uploadDir);

        try {
            $file->move($uploadDir, $fileName);
            return $uploadDir . '/' . $fileName;
        } catch (FileException $e) {
            throw new \Exception($this->translationService->tp('exceptions.upload_error', [
                'error' => $e->getMessage()
            ], 'file_upload_service'));
        }
    }

    /**
     * Upload d'un document générique
     */
    public function uploadDocument(UploadedFile $file, string $directory = 'documents'): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $uploadDir = $this->projectDir . '/var/' . $directory;
        $this->ensureDirectoryExists($uploadDir);

        try {
            $file->move($uploadDir, $fileName);
            return $uploadDir . '/' . $fileName;
        } catch (FileException $e) {
            throw new \Exception($this->translationService->tp('exceptions.upload_error', [
                'error' => $e->getMessage()
            ], 'file_upload_service'));
        }
    }

    /**
     * Vérifie qu'un fichier est un PDF ou une image
     */
    public function validateFileType(UploadedFile $file): bool
    {
        $allowedMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/jpg',
            'image/png'
        ];

        return in_array($file->getMimeType(), $allowedMimeTypes);
    }

    /**
     * Vérifie la taille du fichier (max 10MB par défaut)
     */
    public function validateFileSize(UploadedFile $file, int $maxSizeInMb = 10): bool
    {
        return $file->getSize() <= ($maxSizeInMb * 1024 * 1024);
    }

    /**
     * Validation complète d'un fichier
     */
    public function validateFile(UploadedFile $file): array
    {
        $errors = [];

        if (!$this->validateFileType($file)) {
            $errors[] = $this->translationService->tp('validation_errors.invalid_file_type', [], 'file_upload_service');
        }

        if (!$this->validateFileSize($file)) {
            $errors[] = $this->translationService->tp('validation_errors.file_too_large', [], 'file_upload_service');
        }

        if ($file->getError() !== UPLOAD_ERR_OK) {
            $errors[] = $this->translationService->tp('validation_errors.upload_failed', [], 'file_upload_service');
        }

        return $errors;
    }

    /**
     * Supprime un fichier uploadé
     */
    public function deleteFile(string $filePath): bool
    {
        if (file_exists($filePath) && is_file($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

    /**
     * Obtient la taille d'un fichier en format lisible
     */
    public function getHumanFileSize(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return '0 B';
        }

        $size = filesize($filePath);
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Crée un répertoire s'il n'existe pas
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}
