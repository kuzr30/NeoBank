<?php

namespace App\Controller\Banking;

use App\Entity\Loan;
use App\Entity\User;
use App\Service\KycService;
use App\Repository\LoanRepository;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Controller\Banking\Trait\KycAccessTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route([
    'fr' => '/{_locale}/banking/prets',
    'nl' => '/{_locale}/banking/leningen',
    'en' => '/{_locale}/banking/loans',
    'de' => '/{_locale}/banking/darlehen',
    'es' => '/{_locale}/banking/prestamos'
], requirements: ['_locale' => 'fr|nl|de|en|es'])]
#[IsGranted('ROLE_CLIENT')]
class LoanController extends AbstractController
{
    use KycAccessTrait;
    public function __construct(
        private AccountRepository $accountRepository,
        private TransactionRepository $transactionRepository,
        private LoanRepository $loanRepository,
        private KycService $kycService
    ) {
    }

    #[Route('', name: 'banking_loans')]
    public function index(Request $request): Response
    {
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }
        
        /** @var User $user */
        $user = $this->getUser();
        
        $accounts = $this->accountRepository->findActiveByUser($user);
        $loans = [];
        
        foreach ($accounts as $account) {
            $accountLoans = $this->loanRepository->findByAccount($account);
            $loans = array_merge($loans, $accountLoans);
        }
        
        // Si c'est une requête AJAX, retourner seulement le contenu de l'onglet
        if ($request->isXmlHttpRequest()) {
            return $this->render('banking/tabs/credits.html.twig', [
                'loans' => $loans,
                'user' => $user,
            ]);
        }
        
        return $this->render('banking/dashboard.html.twig', $this->getDashboardData($user, 'credits', [
            'loans' => $loans,
        ]));
    }

    #[Route([
        'fr' => '/{id}',
        'nl' => '/{id}',
        'en' => '/{id}',
        'de' => '/{id}',
        'es' => '/{id}'
    ], name: 'banking_loan_detail')]
    public function show(Loan $loan): Response
    {
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }

        /** @var User $user */
        $user = $this->getUser();
        
        // Vérification que l'utilisateur est propriétaire du compte associé au prêt
        if ($loan->getAccount()->getOwner() !== $user) {
            throw $this->createAccessDeniedException();
        }
        
        $payments = $loan->getPayments();
        
        return $this->render('banking/dashboard.html.twig', $this->getDashboardData($user, 'loan_detail', [
            'loan' => $loan,
            'payments' => $payments,
        ]));
    }

    /**
     * Récupère les données nécessaires pour le dashboard
     */
    private function getDashboardData(User $user, string $activeTab = 'credits', array $additionalData = []): array
    {
        // Récupérer les données de base pour le dashboard
        $accounts = $this->accountRepository->findActiveByUser($user);
        $totalBalance = array_sum(array_map(fn($account) => $account->getBalance(), $accounts));
        
        $recentTransactions = $this->transactionRepository->findRecentByUser($user, 5);
        $loans = $this->loanRepository->findActiveByUser($user);
        
        return array_merge([
            'user' => $user,
            'accounts' => $accounts,
            'total_balance' => $totalBalance,
            'recent_transactions' => $recentTransactions,
            'loans' => $loans,
            'activeTab' => $activeTab,
        ], $additionalData);
    }
}
