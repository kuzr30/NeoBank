<?php

namespace App\Controller\Banking;

use App\Entity\User;
use App\Service\KycService;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Controller\Banking\Trait\KycAccessTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route([
    'fr' => '/{_locale}/banking/transactions',
    'nl' => '/{_locale}/banking/transacties',
    'en' => '/{_locale}/banking/transactions',
    'de' => '/{_locale}/banking/transaktionen',
    'es' => '/{_locale}/banking/transacciones'
], requirements: ['_locale' => 'fr|nl|de|en|es'])]
#[IsGranted('ROLE_CLIENT')]
class TransactionController extends AbstractController
{
    use KycAccessTrait;
    public function __construct(
        private AccountRepository $accountRepository,
        private TransactionRepository $transactionRepository,
        private KycService $kycService
    ) {
    }

    #[Route('', name: 'banking_transactions')]
    public function index(): Response
    {
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }
        
        /** @var User $user */
        $user = $this->getUser();
        
        $accounts = $this->accountRepository->findActiveByUser($user);
        $transactions = [];
        
        foreach ($accounts as $account) {
            $accountTransactions = $this->transactionRepository->findByAccount($account, 20);
            $transactions = array_merge($transactions, $accountTransactions);
        }
        
        // Tri par date décroissante
        usort($transactions, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
        
        return $this->render('banking/transactions.html.twig', [
            'transactions' => array_slice($transactions, 0, 50),
        ]);
    }
}
