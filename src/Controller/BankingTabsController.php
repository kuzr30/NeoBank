<?php

namespace App\Controller;

use App\Repository\BankAccountRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/{_locale}/banking/tabs')]
#[IsGranted('ROLE_CLIENT')]
class BankingTabsController extends AbstractController
{
    #[Route('/ribs', name: 'app_banking_tabs_ribs', methods: ['GET'])]
    public function ribs(BankAccountRepository $bankAccountRepository): Response
    {
        $bankAccounts = $bankAccountRepository->findActiveByUser($this->getUser());

        return $this->render('banking/tabs/ribs.html.twig', [
            'bank_accounts' => $bankAccounts,
            'user' => $this->getUser(),
        ]);
    }
}
