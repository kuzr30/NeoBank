<?php

namespace App\Controller\Admin;

use App\Entity\ContractFee;
use App\Entity\CreditApplication;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

class ContractFeeManagementController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AdminUrlGenerator $adminUrlGenerator,
        private RequestStack $requestStack
    ) {}

    #[Route('/admin/contract-fee/create/{creditApplicationId}', name: 'admin_contract_fee_create')]
    public function createForCredit(int $creditApplicationId): Response
    {
        // Vérifier que la demande de crédit existe
        $creditApplication = $this->entityManager->getRepository(CreditApplication::class)->find($creditApplicationId);
        
        if (!$creditApplication) {
            $this->addFlash('danger', 'Demande de crédit introuvable.');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        // Créer un nouveau frais directement associé à cette demande
        $contractFee = new ContractFee();
        $contractFee->setCreditApplication($creditApplication);
        
        // Sauvegarder temporairement en session pour la pré-sélection
        $session = $this->requestStack->getCurrentRequest()?->getSession();
        if ($session) {
            $session->set('preselected_credit_application_id', $creditApplicationId);
        }

        // Message informatif
        $this->addFlash('info', sprintf(
            'Ajout d\'un frais pour la demande %s (%s %s - %.2f €)',
            $creditApplication->getReferenceNumber(),
            $creditApplication->getFirstName(),
            $creditApplication->getLastName(),
            (float)$creditApplication->getLoanAmount()
        ));

        // Rediriger vers EasyAdmin avec l'URL propre
        $url = $this->adminUrlGenerator
            ->setController(ContractFeeCrudController::class)
            ->setAction(Action::NEW)
            ->generateUrl();

        return $this->redirect($url);
    }
}
