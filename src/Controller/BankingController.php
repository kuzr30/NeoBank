<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Transfer;
use App\Service\KycService;
use App\Service\ProfessionalTranslationService;
use App\Service\CardSubscriptionService;
use App\Service\AssuranceService;
use App\Repository\LoanRepository;
use App\Repository\AccountRepository;
use App\Repository\CreditApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TransactionRepository;
use App\Controller\Banking\Trait\KycAccessTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/{_locale}/banking', requirements: ['_locale' => 'fr|nl|de|en|es'])]
#[IsGranted('ROLE_USER')]
class BankingController extends AbstractController
{
    use KycAccessTrait;
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProfessionalTranslationService $translationService,
        private AccountRepository $accountRepository,
        private TransactionRepository $transactionRepository,
        private LoanRepository $loanRepository,
        private CreditApplicationRepository $creditApplicationRepository,
        private KycService $kycService,
        private CardSubscriptionService $cardSubscriptionService,
        private AssuranceService $assuranceService
    ) {
    }

    #[Route([
        'fr' => '/dashboard',
        'nl' => '/dashboard',
        'en' => '/dashboard',
        'de' => '/dashboard',
        'es' => '/dashboard'
    ], name: 'banking_dashboard', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    #[Route([
        'fr' => '/comptes',
        'nl' => '/rekeningen',
        'en' => '/accounts',
        'de' => '/konten',
        'es' => '/cuentas'
    ], name: 'banking_comptes', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function dashboard(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a accès aux services bancaires (KYC approuvé)
        if (!$this->kycService->canUserAccessBanking($user)) {
            $this->addFlash('warning', $this->translationService->tp('banking_controller.flash.kyc_required', [], 'banking_controller'));
            return $this->redirectToRoute('kyc_index');
        }

        // Récupérer l'onglet actif depuis les paramètres de la requête
        $activeTab = $request->get('activeTab', 'comptes');

        // Si c'est une requête AJAX, retourner seulement le contenu de l'onglet
        if ($request->isXmlHttpRequest()) {
            $dashboardData = $this->getDashboardData($user, $activeTab);
            
            // Retourner le template approprié selon l'onglet actif
            $template = match($activeTab) {
                'cartes' => 'banking/cards/index.html.twig',
                'virements' => 'banking/tabs/virements.html.twig',
                'ribs' => 'banking/tabs/ribs.html.twig',
                'epargne' => 'banking/tabs/epargne.html.twig',
                'credits' => 'banking/tabs/credits.html.twig',
                'assurances' => 'banking/tabs/assurances.html.twig',
                default => 'banking/tabs/comptes.html.twig'
            };
            
            return $this->render($template, $dashboardData);
        }
        
        return $this->render('banking/dashboard.html.twig', $this->getDashboardData($user, $activeTab));
    }



    #[Route([
        'fr' => '/cartes',
        'nl' => '/kaarten',
        'en' => '/cards',
        'de' => '/karten',
        'es' => '/tarjetas'
    ], name: 'banking_cartes', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function cartes(Request $request): Response
    {
        // Rediriger vers le nouveau contrôleur de cartes
        return $this->redirectToRoute('app_banking_card_index', ['_locale' => $request->getLocale()]);
    }

    #[Route([
        'fr' => '/virements',
        'nl' => '/overboekingen',
        'en' => '/transfers',
        'de' => '/uberweisungen',
        'es' => '/transferencias'
    ], name: 'banking_virements', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function virements(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer les virements de l'utilisateur
        $transferRepository = $this->entityManager->getRepository(Transfer::class);
        $active_transfers = $transferRepository->findBy([
            'user' => $user,
            'status' => ['pending', 'executing']
        ], ['createdAt' => 'DESC']);
        
        $completed_transfers = $transferRepository->findBy([
            'user' => $user,
            'status' => 'completed'
        ], ['executedAt' => 'DESC'], 10); // Limiter aux 10 derniers
        
        // Récupération des comptes bancaires de l'utilisateur
        $bankAccounts = $this->entityManager->getRepository(\App\Entity\BankAccount::class)
            ->findActiveByUser($user);
        
        $templateData = [
            'user' => $user,
            'active_transfers' => $active_transfers,
            'completed_transfers' => $completed_transfers,
            'bank_accounts' => $bankAccounts,
        ];
        
        // Si c'est une requête AJAX, retourner seulement le contenu de l'onglet
        if ($request->isXmlHttpRequest()) {
            return $this->render('banking/tabs/transfers.html.twig', $templateData);
        }
        
        // Si c'est une requête directe, retourner le dashboard complet
        return $this->render('banking/dashboard.html.twig', array_merge(
            $this->getDashboardData($user, 'virements'),
            $templateData
        ));
    }

/*    #[Route([
        'fr' => '/epargne',
        'nl' => '/sparen',
        'en' => '/savings',
        'de' => '/sparen',
        'es' => '/ahorros'
    ], name: 'banking_epargne', requirements: ['_locale' => 'fr|nl|de|en|es'])] 
    public function epargne(Request $request): Response
    {
        
        $user = $this->getUser();
   
        if ($request->isXmlHttpRequest()) {
            return $this->render('banking/tabs/epargne.html.twig', [
                'user' => $user,
            ]);
        }
        return $this->render('banking/dashboard.html.twig', $this->getDashboardData($user, 'epargne'));
    } 
*/
    #[Route([
        'fr' => '/credits',
        'nl' => '/kredieten',
        'en' => '/credits',
        'de' => '/kredite',
        'es' => '/creditos'
    ], name: 'banking_credits', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function credits(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier l'accès KYC
        if (!$this->kycService->canUserAccessBanking($user)) {
            $this->addFlash('warning', $this->translationService->tp('banking_controller.flash.kyc_required', [], 'banking_controller'));
            return $this->redirectToRoute('kyc_index');
        }
        
        // Si c'est une requête AJAX, retourner seulement le contenu de l'onglet
        if ($request->isXmlHttpRequest()) {
            $creditData = $this->getCreditData($user);
            return $this->render('banking/tabs/credits.html.twig', $creditData);
        }
        
        // Si c'est une requête directe, retourner le dashboard complet
        return $this->render('banking/dashboard.html.twig', $this->getDashboardData($user, 'credits'));
    }

    #[Route([
        'fr' => '/assurances',
        'nl' => '/verzekeringen',
        'en' => '/insurances',
        'de' => '/versicherungen',
        'es' => '/seguros'
    ], name: 'banking_assurances', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function assurances(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Si c'est une requête AJAX, retourner seulement le contenu de l'onglet
        if ($request->isXmlHttpRequest()) {
            $assuranceData = $this->getAssuranceData($user);
            return $this->render('banking/tabs/assurances.html.twig', array_merge([
                'user' => $user,
            ], $assuranceData));
        }
        
        // Si c'est une requête directe, retourner le dashboard complet
        return $this->render('banking/dashboard.html.twig', $this->getDashboardData($user, 'assurances'));
    }

    #[Route([
        'fr' => '/mes-beneficiaires',
        'nl' => '/mijn-begunstigden',
        'en' => '/my-beneficiaries',
        'de' => '/meine-begunstigten',
        'es' => '/mis-beneficiarios'
    ], name: 'banking_ribs', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function ribs(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupération des RIB de l'utilisateur
        $bankAccounts = $this->entityManager->getRepository(\App\Entity\BankAccount::class)
            ->findActiveByUser($user);
        
        // Si c'est une requête AJAX, retourner seulement le contenu de l'onglet
        if ($request->isXmlHttpRequest()) {
            return $this->render('banking/tabs/ribs.html.twig', [
                'bank_accounts' => $bankAccounts,
                'user' => $user,
            ]);
        }
        
        // Si c'est une requête directe, retourner le dashboard complet avec l'onglet RIB actif
        return $this->render('banking/dashboard.html.twig', $this->getDashboardData($user, 'ribs', [
            'bank_accounts' => $bankAccounts,
        ]));
    }



    /**
     * Prépare toutes les données nécessaires pour le dashboard
     */
    private function getDashboardData(User $user, string $activeTab = 'comptes', array $additionalData = []): array
    {
        // Récupération des comptes de l'utilisateur
        $accounts = $this->accountRepository->findActiveByUser($user);
        
        // Ajouter les variations mensuelles pour chaque compte
        foreach ($accounts as $account) {
            $monthlyChange = $this->transactionRepository->getMonthlyBalanceChange($account);
            $account->monthlyBalanceChange = $monthlyChange;
        }
        
        // Récupération des transactions récentes
        $recentTransactions = [];
        if (!empty($accounts)) {
            $recentTransactions = $this->transactionRepository->findByAccount($accounts[0], 10);
        }
        
        // Pour l'onglet comptes, récupérer toutes les opérations (transactions + virements)
        $allTransactions = [];
        if ($activeTab === 'comptes') {
            // Récupérer les transactions classiques
            $transactions = $this->transactionRepository->findRecentTransactionsByUser($user, 50);
            
            // Récupérer les virements de l'utilisateur
            $transferRepository = $this->entityManager->getRepository(Transfer::class);
            $transfers = $transferRepository->findBy(['user' => $user], ['createdAt' => 'DESC'], 50);
            
            // Convertir les virements en format compatible avec les transactions
            $transfersAsTransactions = [];
            foreach ($transfers as $transfer) {
                $transfersAsTransactions[] = [
                    'id' => 'transfer_' . $transfer->getId(),
                    'createdAt' => $transfer->getCreatedAt(),
                    'description' => $transfer->getDescription() ?: $this->translationService->tp('banking_controller.transactions.transfer_outgoing', [], 'banking_controller'),
                    'category' => 'VIREMENT',
                    'reference' => $transfer->getReference(),
                    'type' => 'debit', // Les virements sont des débits
                    'amount' => $transfer->getAmount(),
                    'status' => $transfer->getStatus(),
                    'balanceAfter' => null, // Les virements n'ont pas de balanceAfter direct
                    'isTransfer' => true
                ];
            }
            
            // Fusionner et trier par date
            $allOperations = array_merge($transactions, $transfersAsTransactions);
            usort($allOperations, function($a, $b) {
                $dateA = is_object($a) ? $a->getCreatedAt() : $a['createdAt'];
                $dateB = is_object($b) ? $b->getCreatedAt() : $b['createdAt'];
                return $dateB <=> $dateA; // Tri décroissant (plus récent en premier)
            });
            
            $allTransactions = array_slice($allOperations, 0, 50); // Limiter à 50 opérations
        }
        
        // Récupération des prêts actifs
        $activeLoans = [];
        foreach ($accounts as $account) {
            $loans = $this->loanRepository->findByAccount($account);
            $activeLoans = array_merge($activeLoans, array_filter($loans, fn($loan) => $loan->getStatus() === 'active'));
        }
        
        // Données de crédit si c'est l'onglet credits
        $creditData = [];
        if ($activeTab === 'credits') {
            $creditData = $this->getCreditData($user);
        }
        
        // Données de cartes si c'est l'onglet cartes
        $cardData = [];
        if ($activeTab === 'cartes') {
            $cardData = $this->getCardData($user);
        }
        
        // Données d'assurance si c'est l'onglet assurances
        $assuranceData = [];
        if ($activeTab === 'assurances') {
            $assuranceData = $this->getAssuranceData($user);
        }
        
        // Calcul du solde total
        $totalBalance = $this->accountRepository->getTotalBalanceForUser($user);
        
        return array_merge([
            'user' => $user,
            'accounts' => $accounts,
            'recent_transactions' => $recentTransactions,
            'all_transactions' => $allTransactions,
            'active_loans' => $activeLoans,
            'total_balance' => $totalBalance,
            'activeTab' => $activeTab,
        ], $additionalData, $creditData, $cardData, $assuranceData);
    }

    /**
     * Récupère les données de crédit pour un utilisateur
     */
    private function getCreditData(User $user): array
    {
        $userEmail = $user->getEmail();
        
        // Récupération des statistiques de crédit
        $creditStats = $this->creditApplicationRepository->getUserCreditStats($userEmail);
        
        // Récupération des crédits approuvés (actifs)
        $approvedCredits = $this->creditApplicationRepository->findApprovedByUserEmail($userEmail);
        
        // Récupération de toutes les demandes pour l'historique
        $allCreditApplications = $this->creditApplicationRepository->findByUserEmail($userEmail);
        
        // Récupération des sous-comptes crédit
        $accounts = $this->accountRepository->findActiveByUser($user);
        $creditSubAccounts = [];
        $totalAvailableCredit = 0;
        
        foreach ($accounts as $account) {
            $subAccountCredit = $account->getSubAccountCredit();
            if ($subAccountCredit && (float) $subAccountCredit->getAmount() > 0) {
                $creditSubAccounts[] = $subAccountCredit;
                $totalAvailableCredit += (float) $subAccountCredit->getAmount();
            }
        }
        
        // Ajouter le crédit disponible aux statistiques
        if (!isset($creditStats['totalAvailableCredit'])) {
            $creditStats['totalAvailableCredit'] = $totalAvailableCredit;
        }
        
        return [
            'creditStats' => $creditStats,
            'approvedCredits' => $approvedCredits,
            'creditApplications' => $allCreditApplications,
            'creditSubAccounts' => $creditSubAccounts,
            'hasCredits' => !empty($approvedCredits),
            'hasApplications' => !empty($allCreditApplications)
        ];
    }
    
    /**
     * Récupère les données de cartes pour un utilisateur
     */
    private function getCardData(User $user): array
    {
        // Utiliser l'injection de dépendance moderne
        $userCardOrSubscription = $this->cardSubscriptionService->getUserCardOrSubscription($user);
        
        return [
            'userCardOrSubscription' => $userCardOrSubscription,
        ];
    }
    
    /**
     * Récupère les données d'assurance pour un utilisateur
     */
    private function getAssuranceData(User $user): array
    {
        return $this->assuranceService->getAssuranceData($user);
    }
}
