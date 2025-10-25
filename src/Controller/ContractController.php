<?php

namespace App\Controller;

use App\Entity\CreditApplication;
use App\Service\CreditWorkflowService;
use App\Service\ProfessionalTranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[Route([
    'fr' => '/{_locale}/contrats',
    'nl' => '/{_locale}/contracten',
    'en' => '/{_locale}/contracts',
    'de' => '/{_locale}/vertrage',
    'es' => '/{_locale}/contratos'
], requirements: ['_locale' => 'fr|nl|de|en|es'])]
class ContractController extends AbstractController
{
    public function __construct(
        private CreditWorkflowService $creditWorkflowService,
        private ProfessionalTranslationService $translationService,
        #[Autowire('%kernel.project_dir%')] private string $projectDir
    ) {}

    #[Route([
        'fr' => '/telecharger/{id}',
        'nl' => '/downloaden/{id}',
        'en' => '/download/{id}',
        'de' => '/herunterladen/{id}',
        'es' => '/descargar/{id}'
    ], name: 'contract_download', requirements: ['_locale' => 'fr|nl|de|en|es'], methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function downloadContract(CreditApplication $application): Response
    {
        // Vérifier que le contrat existe
        if (!$this->creditWorkflowService->contractFileExists($application)) {
            throw $this->createNotFoundException($this->translationService->tp('contract_controller.errors.contract_not_found', [], 'contract_controller'));
        }

        $contractPath = $application->getContractPath();
        $fullPath = $this->projectDir . '/public/' . $contractPath;
        
        // Créer une réponse de téléchargement
        $response = new BinaryFileResponse($fullPath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($contractPath)
        );

        return $response;
    }

    #[Route([
        'fr' => '/voir/{id}',
        'nl' => '/bekijken/{id}',
        'en' => '/view/{id}',
        'de' => '/ansehen/{id}',
        'es' => '/ver/{id}'
    ], name: 'contract_view', requirements: ['_locale' => 'fr|nl|de|en|es'], methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT')]
    public function viewContract(CreditApplication $application): Response
    {
        // Vérifier que le contrat existe
        if (!$this->creditWorkflowService->contractFileExists($application)) {
            throw $this->createNotFoundException($this->translationService->tp('contract_controller.errors.contract_not_found', [], 'contract_controller'));
        }

        $contractPath = $application->getContractPath();
        $fullPath = $this->projectDir . '/public/' . $contractPath;
        
        // Créer une réponse pour affichage inline
        $response = new BinaryFileResponse($fullPath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            basename($contractPath)
        );

        return $response;
    }
}
