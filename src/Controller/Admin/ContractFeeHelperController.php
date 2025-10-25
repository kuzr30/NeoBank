<?php

namespace App\Controller\Admin;

use App\Entity\ContractFee;
use App\Entity\CreditApplication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContractFeeHelperController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/admin/contract-fee/new-simple', name: 'admin_contract_fee_new_simple')]
    public function newSimple(): Response
    {
        // Nettoyage de la session pour éviter les conflits
        $session = $this->container->get('request_stack')->getCurrentRequest()->getSession();
        $session->remove('ea.crud.searchForm');
        $session->remove('ea.crud.filters');
        
        // Redirection avec URL manuelle complète
        return $this->redirect('/admin?crudAction=new&crudControllerFqcn=App%5CController%5CAdmin%5CContractFeeCrudController');
    }
}
