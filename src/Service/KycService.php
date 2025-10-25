<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\KycSubmission;
use App\Entity\KycDocument;
use App\Message\KycStatusNotificationMessage;
use App\Message\NewKycSubmissionMessage;
use App\Repository\KycSubmissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Psr\Log\LoggerInterface;

class KycService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private KycSubmissionRepository $kycSubmissionRepository,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger
    ) {
    }

    public function createKycSubmission(User $user, array $documentPaths, string $locale = 'fr'): KycSubmission
    {
        $this->logger->info('KycService::createKycSubmission called', [
            'user_id' => $user->getId(),
            'document_paths' => $documentPaths,
            'locale' => $locale
        ]);

        // Vérifier si l'utilisateur a déjà une soumission KYC
        $existingSubmission = $this->kycSubmissionRepository->findByUser($user);
        
        $this->logger->info('Existing submission check', [
            'existing_submission_id' => $existingSubmission?->getId(),
            'existing_status' => $existingSubmission?->getStatus()
        ]);
        
        if ($existingSubmission) {
            // Seules les soumissions rejetées peuvent être re-soumises
            if ($existingSubmission->isPending() || $existingSubmission->isApproved()) {
                $this->logger->error('Cannot resubmit - existing submission not rejected', [
                    'status' => $existingSubmission->getStatus()
                ]);
                throw new \InvalidArgumentException('Une soumission KYC est déjà en cours ou approuvée pour cet utilisateur.');
            }
            
            // Si la soumission précédente a été rejetée, on réutilise la même soumission
            if ($existingSubmission->isRejected()) {
                $this->logger->info('Reusing rejected submission', [
                    'submission_id' => $existingSubmission->getId()
                ]);
                
                // Réinitialiser la soumission
                $existingSubmission->setStatus(KycSubmission::STATUS_PENDING);
                $existingSubmission->setSubmittedAt(new \DateTimeImmutable());
                $existingSubmission->setProcessedAt(null);
                $existingSubmission->setProcessedBy(null);
                $existingSubmission->setAdminNotes(null);
                $existingSubmission->setSubmissionLocale($locale);
                
                $this->logger->info('Submission reset, removing old documents', [
                    'old_documents_count' => count($existingSubmission->getDocuments())
                ]);
                
                // Supprimer les anciens documents
                foreach ($existingSubmission->getDocuments() as $document) {
                    $existingSubmission->removeDocument($document);
                    $this->entityManager->remove($document);
                }
                
                $this->entityManager->persist($existingSubmission);
                
                try {
                    // Traiter les nouveaux documents
                    $this->processDocuments($existingSubmission, $documentPaths);
                    
                    // Vérifier si tous les documents requis sont présents
                    if (!$existingSubmission->hasRequiredDocuments()) {
                        $existingSubmission->setStatus(KycSubmission::STATUS_INCOMPLETE);
                    }
                    
                    $this->logger->info('Before flush - resubmission');
                    $this->entityManager->flush();
                    $this->logger->info('After flush - resubmission successful');
                    
                } catch (\Exception $e) {
                    $this->logger->error('Error during resubmission process', [
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
                
                // Envoyer les notifications par email (après le flush pour éviter de planter la transaction)
                try {
                    $this->logger->info('Dispatching email notification');
                    $this->messageBus->dispatch(new NewKycSubmissionMessage($existingSubmission->getId()));
                } catch (\Exception $e) {
                    $this->logger->error('Failed to dispatch email notification, but KYC submission was successful', [
                        'exception' => $e->getMessage(),
                        'submission_id' => $existingSubmission->getId()
                    ]);
                    // Ne pas rethrow l'exception car la soumission KYC a réussi
                }
                
                $this->logger->info('Soumission KYC re-soumise', [
                    'user_id' => $user->getId(),
                    'submission_id' => $existingSubmission->getId(),
                    'status' => $existingSubmission->getStatus()
                ]);
                
                return $existingSubmission;
            }
        }

        $this->logger->info('Creating new submission (first time)');

        // Créer une nouvelle soumission (première fois)
        $submission = new KycSubmission();
        $submission->setUser($user);
        $submission->setStatus(KycSubmission::STATUS_PENDING);
        $submission->setSubmittedAt(new \DateTimeImmutable());
        $submission->setSubmissionLocale($locale);

        $this->entityManager->persist($submission);

        try {
            // Traiter les documents
            $this->processDocuments($submission, $documentPaths);

            // Vérifier si tous les documents requis sont présents
            if (!$submission->hasRequiredDocuments()) {
                $submission->setStatus(KycSubmission::STATUS_INCOMPLETE);
            }

            $this->logger->info('Before flush - new submission');
            $this->entityManager->flush();
            $this->logger->info('After flush - new submission successful');
            
        } catch (\Exception $e) {
            $this->logger->error('Error during new submission process', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        // Envoyer les notifications par email (après le flush pour éviter de planter la transaction)
        try {
            $this->logger->info('Dispatching email notification');
            $this->messageBus->dispatch(new NewKycSubmissionMessage($submission->getId()));
        } catch (\Exception $e) {
            $this->logger->error('Failed to dispatch email notification, but KYC submission was successful', [
                'exception' => $e->getMessage(),
                'submission_id' => $submission->getId()
            ]);
            // Ne pas rethrow l'exception car la soumission KYC a réussi
        }

        $this->logger->info('Soumission KYC créée', [
            'user_id' => $user->getId(),
            'submission_id' => $submission->getId(),
            'status' => $submission->getStatus()
        ]);

        return $submission;
    }

    public function approveKycSubmission(KycSubmission $submission, User $admin, ?string $notes = null): void
    {
        $submission->setStatus(KycSubmission::STATUS_APPROVED);
        $submission->setProcessedAt(new \DateTimeImmutable());
        $submission->setProcessedBy($admin);
        $submission->setAdminNotes($notes);

        // Attribuer le rôle CLIENT à l'utilisateur
        $user = $submission->getUser();
        $roles = $user->getRoles();
        
        if (!in_array('ROLE_CLIENT', $roles)) {
            $roles[] = 'ROLE_CLIENT';
            $user->setRoles($roles);
        }

        $this->entityManager->flush();

        // Envoyer une notification email
        $this->messageBus->dispatch(new KycStatusNotificationMessage(
            $submission->getId(),
            KycSubmission::STATUS_APPROVED
        ));

        $this->logger->info('Soumission KYC approuvée', [
            'user_id' => $user->getId(),
            'submission_id' => $submission->getId(),
            'approved_by' => $admin->getId()
        ]);
    }

    public function rejectKycSubmission(KycSubmission $submission, User $admin, string $reason): void
    {
        $submission->setStatus(KycSubmission::STATUS_REJECTED);
        $submission->setProcessedAt(new \DateTimeImmutable());
        $submission->setProcessedBy($admin);
        $submission->setAdminNotes($reason);

        $this->entityManager->flush();

        // Envoyer une notification email
        $this->messageBus->dispatch(new KycStatusNotificationMessage(
            $submission->getId(),
            KycSubmission::STATUS_REJECTED
        ));

        $this->logger->info('Soumission KYC rejetée', [
            'user_id' => $submission->getUser()->getId(),
            'submission_id' => $submission->getId(),
            'rejected_by' => $admin->getId(),
            'reason' => $reason
        ]);
    }

    public function canUserAccessBanking(User $user): bool
    {
        return $user->isClient() && $user->isKycApproved();
    }

    public function getUserKycStatus(User $user): string
    {
        $submission = $this->kycSubmissionRepository->findByUser($user);
        
        if (!$submission) {
            return 'not_submitted';
        }

        return $submission->getStatus();
    }

    private function processDocuments(KycSubmission $submission, array $documentPaths): void
    {
        $documentTypeMapping = [
            'identityDocument' => KycDocument::TYPE_IDENTITY,
            'incomeDocument' => KycDocument::TYPE_INCOME_PROOF,
            'addressDocument' => KycDocument::TYPE_ADDRESS_PROOF,
            'selfieDocument' => KycDocument::TYPE_SELFIE,
        ];

        foreach ($documentPaths as $fieldName => $filePath) {
            if (empty($filePath) || !isset($documentTypeMapping[$fieldName])) {
                continue;
            }

            $document = new KycDocument();
            $document->setKycSubmission($submission);
            $document->setType($documentTypeMapping[$fieldName]);
            
            // Extraire le nom du fichier et définir les propriétés requises
            $filename = basename($filePath);
            $document->setFilename($filename);
            $document->setOriginalName($filename);
            
            // Déterminer le type MIME basé sur l'extension
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $mimeType = match($extension) {
                'pdf' => 'application/pdf',
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                default => 'application/octet-stream'
            };
            $document->setMimeType($mimeType);
            
            // Définir une taille fictive (en production, cela viendrait du fichier réel)
            $document->setFileSize(1024); // 1KB par défaut

            $submission->addDocument($document);
            $this->entityManager->persist($document);
        }
    }
}
