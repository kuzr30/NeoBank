<?php

namespace App\Controller;

use App\Entity\CreditApplication;
use App\Service\CreditWorkflowService;
use App\Repository\CreditApplicationRepository;
use App\Service\ProfessionalTranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route([
    'fr' => '/{_locale}/contrat-credit',
    'nl' => '/{_locale}/krediet-contract',
    'en' => '/{_locale}/credit-contract',
    'de' => '/{_locale}/kredit-vertrag',
    'es' => '/{_locale}/contrato-credito'
], requirements: ['_locale' => 'fr|nl|de|en|es'])]
#[IsGranted('ROLE_USER')]
class CreditContractController extends AbstractController
{
    public function __construct(
        private CreditWorkflowService $creditWorkflowService,
        private CreditApplicationRepository $creditApplicationRepository,
        private ProfessionalTranslationService $translationService
    ) {}

    #[Route([
        'fr' => '/telecharger/{contractNumber}',
        'nl' => '/uploaden/{contractNumber}',
        'en' => '/upload/{contractNumber}',
        'de' => '/hochladen/{contractNumber}',
        'es' => '/subir/{contractNumber}'
    ], name: 'credit_contract_upload', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function uploadSignedContract(
        string $contractNumber,
        Request $request
    ): Response {
        // Trouver la demande de crédit par le numéro de contrat
        $creditApplication = $this->findApplicationByContractNumber($contractNumber);
        
        if (!$creditApplication) {
            $this->addFlash('danger', 
                $this->translationService->tp('flash.contract_not_found', [], 'credit_contract_controller')
            );
            return $this->redirectToRoute('banking_dashboard');
        }

        // Vérifier que c'est le bon utilisateur
        if ($creditApplication->getEmail() !== $this->getUser()->getEmail()) {
            throw $this->createAccessDeniedException('Accès non autorisé à ce contrat.');
        }

        if ($request->isMethod('POST')) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $request->files->get('signed_contract');
            
            if ($uploadedFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/var/signed_contracts';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $filename = $contractNumber . '_signed_' . date('YmdHis') . '.' . $uploadedFile->guessExtension();
                
                try {
                    $uploadedFile->move($uploadDir, $filename);
                    $signedContractPath = $uploadDir . '/' . $filename;

                    // Notifier le workflow de la signature
                    $this->creditWorkflowService->notifyContractSigned($creditApplication, $signedContractPath);

                    $this->addFlash('success', 
                        $this->translationService->tp('flash.contract_uploaded_success', [], 'credit_contract_controller')
                    );
                    
                    return $this->redirectToRoute('banking_dashboard');
                    
                } catch (FileException $e) {
                    $this->addFlash('danger', 
                        $this->translationService->tp('flash.upload_error', [], 'credit_contract_controller')
                    );
                }
            } else {
                $this->addFlash('danger', 
                    $this->translationService->tp('flash.no_file_selected', [], 'credit_contract_controller')
                );
            }
        }

        return $this->render('credit_application/upload_contract.html.twig', [
            'creditApplication' => $creditApplication,
            'contractNumber' => $contractNumber
        ]);
    }

    #[Route([
        'fr' => '/statut/{contractNumber}',
        'nl' => '/status/{contractNumber}',
        'en' => '/status/{contractNumber}',
        'de' => '/status/{contractNumber}',
        'es' => '/estado/{contractNumber}'
    ], name: 'credit_contract_status', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function contractStatus(string $contractNumber): Response
    {
        $creditApplication = $this->findApplicationByContractNumber($contractNumber);
        
        if (!$creditApplication) {
            $this->addFlash('danger', 
                $this->translationService->tp('flash.contract_not_found', [], 'credit_contract_controller')
            );
            return $this->redirectToRoute('banking_dashboard');
        }

        // Vérifier que c'est le bon utilisateur
        if ($creditApplication->getEmail() !== $this->getUser()->getEmail()) {
            throw $this->createAccessDeniedException('Accès non autorisé à ce contrat.');
        }

        return $this->render('credit_application/contract_status.html.twig', [
            'creditApplication' => $creditApplication,
            'contractNumber' => $contractNumber
        ]);
    }

    private function findApplicationByContractNumber(string $contractNumber): ?CreditApplication
    {
        // Le numéro de contrat suit le format: CREDIT-{id}-{year}
        if (preg_match('/CREDIT-(\d+)-(\d{4})/', $contractNumber, $matches)) {
            $applicationId = $matches[1];
            return $this->creditApplicationRepository->find($applicationId);
        }
        
        return null;
    }
}
