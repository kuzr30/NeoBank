<?php

namespace App\Controller\Banking;

use App\Entity\User;
use App\Entity\Account;
use App\Entity\Transfer;
use App\Service\KycService;
use App\Service\ProfessionalTranslationService;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Repository\LoanRepository;
use App\Controller\Banking\Trait\KycAccessTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route([
    'fr' => '/{_locale}/banking/comptes',
    'nl' => '/{_locale}/banking/rekeningen',
    'en' => '/{_locale}/banking/accounts',
    'de' => '/{_locale}/banking/konten',
    'es' => '/{_locale}/banking/cuentas'
], requirements: ['_locale' => 'fr|nl|de|en|es'])]
#[IsGranted('ROLE_CLIENT')]
class AccountController extends AbstractController
{
    use KycAccessTrait;
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccountRepository $accountRepository,
        private TransactionRepository $transactionRepository,
        private LoanRepository $loanRepository,
        private KycService $kycService,
        private ProfessionalTranslationService $translationService
    ) {
    }

    #[Route('', name: 'banking_accounts')]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a accès aux services bancaires (KYC approuvé)
        if (!$this->kycService->canUserAccessBanking($user)) {
            $this->addFlash('warning', 
                $this->translationService->tp('flash.kyc_verification_required', [], 'banking_account_controller')
            );
            return $this->redirectToRoute('kyc_index');
        }

        // Si c'est une requête AJAX, retourner seulement le contenu de l'onglet comptes
        if ($request->isXmlHttpRequest()) {
            $dashboardData = $this->getDashboardData($user, 'comptes');
            return $this->render('banking/tabs/comptes.html.twig', $dashboardData);
        }
        
        return $this->render('banking/dashboard.html.twig', $this->getDashboardData($user, 'comptes'));
    }

    #[Route([
        'fr' => '/{id}',
        'nl' => '/{id}',
        'en' => '/{id}',
        'de' => '/{id}',
        'es' => '/{id}'
    ], name: 'banking_account_detail')]
    public function show(Account $account): Response
    {
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }

        /** @var User $user */
        $user = $this->getUser();
        
        // Vérification que l'utilisateur est propriétaire du compte
        if ($account->getOwner() !== $user) {
            throw $this->createAccessDeniedException();
        }
        
        $transactions = $this->transactionRepository->findByAccount($account, 50);
        
        return $this->render('banking/dashboard.html.twig', $this->getDashboardData($user, 'account_detail', [
            'account' => $account,
            'transactions' => $transactions,
        ]));
    }

    /**
     * Récupère les données nécessaires pour le dashboard
     */
    private function getDashboardData(User $user, string $activeTab = 'comptes', array $additionalData = []): array
    {
        // Récupérer les données de base pour le dashboard
        $accounts = $this->accountRepository->findActiveByUser($user);
        $totalBalance = array_sum(array_map(fn($account) => $account->getBalance(), $accounts));
        
        $recentTransactions = $this->transactionRepository->findRecentByUser($user, 5);
        $loans = $this->loanRepository->findActiveByUser($user);
        
        // Pour l'onglet comptes, récupérer plus de transactions
        $allTransactions = [];
        if ($activeTab === 'comptes') {
            $allTransactions = $this->transactionRepository->findRecentByUser($user, 50);
        }
        
        return array_merge([
            'user' => $user,
            'accounts' => $accounts,
            'total_balance' => $totalBalance,
            'recent_transactions' => $recentTransactions,
            'all_transactions' => $allTransactions,
            'loans' => $loans,
            'activeTab' => $activeTab,
        ], $additionalData);
    }
}
