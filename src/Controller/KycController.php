<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\KycSubmissionFormType;
use App\Service\KycService;
use App\Service\ProfessionalTranslationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/{_locale}/profile/kyc', name: 'kyc_', requirements: ['_locale' => 'fr|nl|de|en|es'])]
#[IsGranted('ROLE_USER')]
class KycController extends AbstractController
{
    public function __construct(
        private KycService $kycService,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
        private ProfessionalTranslationService $translationService
    ) {
    }

    #[Route([
        'fr' => '/',
        'nl' => '/',
        'en' => '/',
        'de' => '/',
        'es' => '/'
    ], name: 'index', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $kycStatus = $this->kycService->getUserKycStatus($user);
        
        // Rediriger vers le dashboard bancaire si KYC déjà approuvé
        if ($kycStatus === 'approved') {
            return $this->redirectToRoute('banking_dashboard');
        }
        
        $kycSubmission = $user->getKycSubmission();

        return $this->render('profile/kyc/index.html.twig', [
            'user' => $user,
            'kyc_status' => $kycStatus,
            'kyc_submission' => $kycSubmission,
        ]);
    }

    #[Route([
        'fr' => '/soumettre',
        'nl' => '/indienen',
        'en' => '/submit',
        'de' => '/einreichen',
        'es' => '/enviar'
    ], name: 'submit', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function submit(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a déjà une soumission KYC
        $existingSubmission = $user->getKycSubmission();
        if ($existingSubmission !== null) {
            // Permettre une nouvelle soumission seulement si la précédente a été rejetée
            if (!$existingSubmission->isRejected()) {
                $status = $existingSubmission->getStatusLabel();
                $this->addFlash('warning', 
                    $this->translationService->tp('flash.existing_submission', ['%status%' => $status], 'kyc_controller')
                );
                return $this->redirectToRoute('kyc_index');
            }
        }

        $form = $this->createForm(KycSubmissionFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->info('KYC Form submitted - debugging start', [
                'user_id' => $user->getId(),
                'form_data' => $form->getData(),
                'is_resubmission' => $existingSubmission && $existingSubmission->isRejected()
            ]);
            
            try {
                // Gérer les fichiers et créer la soumission KYC
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/kyc';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $files = [
                    'identityDocument' => $form['identityDocument']->getData(),
                    'incomeDocument' => $form['incomeDocument']->getData(),
                    'addressDocument' => $form['addressDocument']->getData(),
                    'selfieDocument' => $form['selfieDocument']->getData(),
                ];

                $this->logger->info('Files collected', [
                    'files_count' => count(array_filter($files)),
                    'files_info' => array_map(fn($file) => $file ? $file->getClientOriginalName() : null, $files)
                ]);

                $filePaths = [];
                foreach ($files as $type => $file) {
                    if ($file) {
                        $fileName = uniqid() . '_' . $type . '.' . $file->guessExtension();
                        $file->move($uploadDir, $fileName);
                        $filePaths[$type] = 'uploads/kyc/' . $fileName;
                        $this->logger->info('File moved', ['type' => $type, 'filename' => $fileName]);
                    }
                }

                $this->logger->info('Before KYC service call', ['file_paths' => $filePaths]);

                // Créer et envoyer la soumission KYC
                $currentLocale = $request->getLocale();
                $submission = $this->kycService->createKycSubmission($user, $filePaths, $currentLocale);

                $this->logger->info('KYC submission created successfully', [
                    'submission_id' => $submission->getId(),
                    'status' => $submission->getStatus(),
                    'documents_count' => count($submission->getDocuments())
                ]);

                $this->addFlash('success', 
                    $this->translationService->tp('flash.submission_success', [], 'kyc_controller')
                );
                
                $this->logger->info('About to redirect to kyc_index');
                return $this->redirectToRoute('kyc_index');

            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la soumission KYC: ' . $e->getMessage(), [
                    'exception_class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                $form->addError(new FormError('Une erreur est survenue lors de la soumission de votre demande. Veuillez réessayer.'));
            }
        } else {
            if ($form->isSubmitted()) {
                $this->logger->error('Form submitted but invalid', [
                    'form_errors' => (string) $form->getErrors(true, false),
                    'form_data' => $form->getData()
                ]);
            }
        }

        return $this->render('profile/kyc/submit.html.twig', [
            'form' => $form->createView(),
            'is_resubmission' => $existingSubmission && $existingSubmission->isRejected(),
            'existing_submission' => $existingSubmission,
        ]);
    }

    #[Route([
        'fr' => '/statut',
        'nl' => '/status',
        'en' => '/status',
        'de' => '/status',
        'es' => '/estado'
    ], name: 'status', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function status(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $kycSubmission = $user->getKycSubmission();

        if (!$kycSubmission) {
            return $this->redirectToRoute('kyc_index');
        }

        return $this->render('profile/kyc/status.html.twig', [
            'user' => $user,
            'submission' => $kycSubmission,
        ]);
    }

    #[Route('/document/{filename}', name: 'view_document', requirements: ['filename' => '.+'])]
    public function viewDocument(string $filename): Response
    {
        // Vérifier que l'utilisateur est admin ou que le document lui appartient
        if (!$this->isGranted('ROLE_ADMIN')) {
            /** @var User $user */
            $user = $this->getUser();
            $kycSubmission = $user->getKycSubmission();
            
            if (!$kycSubmission) {
                throw $this->createNotFoundException('Document not found');
            }
            
            // Vérifier que le document appartient bien à cet utilisateur
            $documentFound = false;
            foreach ($kycSubmission->getDocuments() as $document) {
                if ($document->getFilename() === $filename) {
                    $documentFound = true;
                    break;
                }
            }
            
            if (!$documentFound) {
                throw $this->createNotFoundException('Document not found');
            }
        }
        
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/kyc/' . $filename;
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found: ' . $filename);
        }
        
        return $this->file($filePath);
    }
}
