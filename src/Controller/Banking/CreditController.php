<?php

namespace App\Controller\Banking;

use App\Entity\Loan;
use App\Entity\User;
use App\Entity\Account;
use App\Entity\CreditApplication;
use App\Service\KycService;
use App\Service\ProfessionalTranslationService;
use App\Repository\LoanRepository;
use App\Repository\AccountRepository;
use App\Repository\CreditApplicationRepository;
use App\Controller\Banking\Trait\KycAccessTrait;
use App\Service\AmortizationService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TransactionRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route([
    'fr' => '/{_locale}/banking/credits',
    'nl' => '/{_locale}/banking/kredieten',
    'en' => '/{_locale}/banking/credits',
    'de' => '/{_locale}/banking/kredite',
    'es' => '/{_locale}/banking/creditos'
], requirements: ['_locale' => 'fr|nl|de|en|es'])]
#[IsGranted('ROLE_CLIENT')]
class CreditController extends AbstractController
{
    use KycAccessTrait;
    
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccountRepository $accountRepository,
        private TransactionRepository $transactionRepository,
        private LoanRepository $loanRepository,
        private CreditApplicationRepository $creditApplicationRepository,
        private KycService $kycService,
        private PaginatorInterface $paginator,
        private AmortizationService $amortizationService,
        private ProfessionalTranslationService $translationService
    ) {
    }

    #[Route('', name: 'banking_credits_detailed')]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }
        
        // Récupération des demandes de crédit approuvées de l'utilisateur
        $approvedCredits = $this->creditApplicationRepository->findBy([
            'email' => $user->getEmail(),
            'status' => ['approved', 'contract_sent', 'contract_signed', 'contract_validated', 'funds_disbursed']
        ]);
        
        // Récupération des prêts actifs de l'utilisateur
        $accounts = $this->accountRepository->findActiveByUser($user);
        $activeLoans = [];
        $creditSubAccounts = [];
        
        foreach ($accounts as $account) {
            $loans = $this->loanRepository->findByAccount($account);
            $activeLoans = array_merge($activeLoans, array_filter($loans, fn($loan) => $loan->getStatus() === 'active'));
            
            // Récupérer le sous-compte crédit s'il existe
            $subAccountCredit = $account->getSubAccountCredit();
            if ($subAccountCredit && (float) $subAccountCredit->getAmount() > 0) {
                $creditSubAccounts[] = $subAccountCredit;
            }
        }
        
        // Calcul des statistiques
        $creditStats = $this->creditApplicationRepository->getUserCreditStats($user->getEmail());
        
        // Si c'est une requête AJAX, retourner seulement le contenu de l'onglet
        if ($request->isXmlHttpRequest()) {
            return $this->render('banking/tabs/credits.html.twig', [
                'user' => $user,
                'approvedCredits' => $approvedCredits,
                'active_loans' => $activeLoans,
                'creditSubAccounts' => $creditSubAccounts,
                'creditStats' => $creditStats,
                'hasCredits' => !empty($approvedCredits) || !empty($activeLoans),
                'hasApplications' => !empty($this->creditApplicationRepository->findBy(['email' => $user->getEmail()])),
                'creditApplications' => $this->creditApplicationRepository->findBy(['email' => $user->getEmail()]),
            ]);
        }
        
        // Si c'est une requête directe, retourner le dashboard complet
        return $this->render('banking/dashboard.html.twig', $this->getDashboardData($user, 'credits', [
            'approvedCredits' => $approvedCredits,
            'active_loans' => $activeLoans,
            'creditSubAccounts' => $creditSubAccounts,
            'creditStats' => $creditStats,
            'hasCredits' => !empty($approvedCredits) || !empty($activeLoans),
            'hasApplications' => !empty($this->creditApplicationRepository->findBy(['email' => $user->getEmail()])),
            'creditApplications' => $this->creditApplicationRepository->findBy(['email' => $user->getEmail()]),
        ]));
    }

    #[Route([
        'fr' => '/{id}',
        'nl' => '/{id}',
        'en' => '/{id}',
        'de' => '/{id}',
        'es' => '/{id}'
    ], name: 'banking_credit_detail')]
    public function show(Loan $loan): Response
    {
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }
        
        // Vérification que l'utilisateur est propriétaire du compte associé au prêt
        if ($loan->getAccount()->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        
        $payments = $loan->getPayments();
        
        return $this->render('banking/credit_detail.html.twig', [
            'loan' => $loan,
            'payments' => $payments,
        ]);
    }

    #[Route([
        'fr' => '/demande/{id}',
        'nl' => '/aanvraag/{id}',
        'en' => '/application/{id}',
        'de' => '/antrag/{id}',
        'es' => '/solicitud/{id}'
    ], name: 'banking_credit_application_detail')]
    public function showApplication(
        CreditApplication $creditApplication, 
        #[MapQueryParameter] int $page = 1
    ): Response
    {
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }
        
        // Vérification que l'utilisateur est propriétaire de la demande
        if ($creditApplication->getEmail() !== $this->getUser()->getEmail()) {
            throw $this->createAccessDeniedException();
        }
        
        $user = $this->getUser();
        $accounts = $this->accountRepository->findActiveByUser($user);
        $amortizationPagination = null;
        
        // Vérifier si un tableau d'amortissement existe, et le créer si nécessaire
        if (!$this->amortizationService->hasAmortizationSchedule($creditApplication)) {
            try {
                $this->amortizationService->generateAndSaveAmortizationSchedule($creditApplication);
            } catch (\Exception $e) {
                // Log l'erreur mais continue
                error_log("Erreur génération tableau d'amortissement: " . $e->getMessage());
            }
        }
        
        // Récupérer le tableau d'amortissement avec pagination
        if ($this->amortizationService->hasAmortizationSchedule($creditApplication)) {
            $amortizationTable = $this->amortizationService->getAmortizationTableFromDatabase($creditApplication);
            
            // Paginer les résultats avec KnpPaginator
            $amortizationPagination = $this->paginator->paginate(
                $amortizationTable, // Les données à paginer
                $page, // Page courante depuis MapQueryParameter
                15 // Items par page
            );
        }
        
        return $this->render('banking/credit_detail_standalone.html.twig', [
            'creditApplication' => $creditApplication,
            'user' => $user,
            'accounts' => $accounts,
            'activeTab' => 'credits',
            'amortization_pagination' => $amortizationPagination,
        ]);
    }

    #[Route([
        'fr' => '/{id}/amortissement',
        'nl' => '/{id}/afschrijving',
        'en' => '/{id}/amortization',
        'de' => '/{id}/tilgung',
        'es' => '/{id}/amortizacion'
    ], name: 'banking_credit_amortization')]
    public function amortization(Loan $loan): Response
    {
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }
        
        // Vérification que l'utilisateur est propriétaire du compte associé au prêt
        if ($loan->getAccount()->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        
        // Générer le tableau d'amortissement
        $amortizationTable = $this->generateAmortizationTable($loan);
        
        return $this->render('banking/credit_amortization.html.twig', [
            'loan' => $loan,
            'amortization_table' => $amortizationTable,
        ]);
    }

    #[Route([
        'fr' => '/simulation/nouveau',
        'nl' => '/simulatie/nieuw',
        'en' => '/simulation/new',
        'de' => '/simulation/neu',
        'es' => '/simulacion/nuevo'
    ], name: 'banking_credit_simulation')]
    public function simulation(Request $request): Response
    {
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Logique de simulation de crédit
        // TODO: Implémenter le formulaire de simulation
        
        return $this->render('banking/credit_simulation.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route([
        'fr' => '/demande/nouvelle',
        'nl' => '/aanvraag/nieuw',
        'en' => '/application/new',
        'de' => '/antrag/neu',
        'es' => '/solicitud/nueva'
    ], name: 'banking_credit_application')]
    public function application(Request $request): Response
    {
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Logique de demande de crédit
        // TODO: Implémenter le formulaire de demande
        
        return $this->render('banking/credit_application.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Génère le tableau d'amortissement pour un prêt
     */
    private function generateAmortizationTable(Loan $loan): array
    {
        $table = [];
        $remainingAmount = (float) $loan->getAmount();
        $monthlyPayment = (float) $loan->getMonthlyPayment();
        $interestRate = (float) $loan->getInterestRate() / 100 / 12; // Taux mensuel
        $termMonths = $loan->getTermMonths();
        
        for ($month = 1; $month <= $termMonths; $month++) {
            $interestPayment = $remainingAmount * $interestRate;
            $principalPayment = $monthlyPayment - $interestPayment;
            
            // S'assurer que le capital ne dépasse pas le montant restant
            if ($principalPayment > $remainingAmount) {
                $principalPayment = $remainingAmount;
                $monthlyPayment = $principalPayment + $interestPayment;
            }
            
            $table[] = [
                'month' => $month,
                'monthly_payment' => $monthlyPayment,
                'principal_payment' => $principalPayment,
                'interest_payment' => $interestPayment,
                'remaining_amount' => $remainingAmount - $principalPayment,
                'date' => (clone $loan->getApprovedAt())->modify("+{$month} months")
            ];
            
            $remainingAmount -= $principalPayment;
            
            // Arrêter si le prêt est remboursé
            if ($remainingAmount <= 0) {
                break;
            }
        }
        
        return $table;
    }

    /**
     * Génère le tableau d'amortissement pour une demande de crédit
     */
    private function generateAmortizationTableFromApplication(CreditApplication $creditApplication): array
    {
        $table = [];
        $remainingAmount = (float) $creditApplication->getLoanAmount();
        $monthlyPayment = (float) $creditApplication->getMonthlyPayment();
        $interestRate = 0.0145 / 12; // Taux d'exemple 1.45% annuel
        $termMonths = $creditApplication->getDuration();
        
        // Si la durée est en années, convertir en mois
        if ($creditApplication->getDurationUnit()->value === 'years') {
            $termMonths *= 12;
        }
        
        for ($month = 1; $month <= $termMonths; $month++) {
            $interestPayment = $remainingAmount * $interestRate;
            $principalPayment = $monthlyPayment - $interestPayment;
            
            // S'assurer que le capital ne dépasse pas le montant restant
            if ($principalPayment > $remainingAmount) {
                $principalPayment = $remainingAmount;
                $monthlyPayment = $principalPayment + $interestPayment;
            }
            
            $table[] = [
                'month' => $month,
                'monthly_payment' => $monthlyPayment,
                'principal_payment' => $principalPayment,
                'interest_payment' => $interestPayment,
                'remaining_amount' => $remainingAmount - $principalPayment,
                'date' => (clone $creditApplication->getCreatedAt())->modify("+{$month} months")
            ];
            
            $remainingAmount -= $principalPayment;
            
            // Arrêter si le prêt est remboursé
            if ($remainingAmount <= 0) {
                break;
            }
        }
        
        return $table;
    }

    /**
     * Vérifie si l'utilisateur peut accéder aux services bancaires
     */
    private function checkKycAccess(): ?Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->kycService->canUserAccessBanking($user)) {
            $this->addFlash('warning', 
                $this->translationService->tp('flash.kyc_verification_required', [], 'banking_credit_controller')
            );
            return $this->redirectToRoute('kyc_index');
        }
        
        return null;
    }

    /**
     * Prépare les données pour le dashboard (méthode commune)
     */
    private function getDashboardData(User $user, string $activeTab = 'credits', array $additionalData = []): array
    {
        // Récupération des comptes de l'utilisateur
        $accounts = $this->accountRepository->findActiveByUser($user);
        
        // Récupération des transactions récentes
        $recentTransactions = [];
        if (!empty($accounts)) {
            $recentTransactions = $this->transactionRepository->findByAccount($accounts[0], 10);
        }
        
        // Récupération des prêts actifs
        $activeLoans = [];
        foreach ($accounts as $account) {
            $loans = $this->loanRepository->findByAccount($account);
            $activeLoans = array_merge($activeLoans, array_filter($loans, fn($loan) => $loan->getStatus() === 'active'));
        }
        
        // Calcul du solde total
        $totalBalance = $this->accountRepository->getTotalBalanceForUser($user);
        
        $baseData = [
            'user' => $user,
            'accounts' => $accounts,
            'recent_transactions' => $recentTransactions,
            'active_loans' => $activeLoans,
            'total_balance' => $totalBalance,
            'activeTab' => $activeTab,
        ];

        // Si l'onglet actif est 'credits', ajouter les données spécifiques aux crédits
        if ($activeTab === 'credits') {
            // Récupération des crédits approuvés
            $approvedCredits = $this->creditApplicationRepository->findApprovedByUserEmail($user->getEmail());
            
            // Récupération des sous-comptes crédit
            $creditSubAccounts = [];
            foreach ($accounts as $account) {
                if ($account->getSubAccountCredit() && $account->getSubAccountCredit()->getAmount() > 0) {
                    $creditSubAccounts[] = $account->getSubAccountCredit();
                }
            }
            
            // Calcul des statistiques
            $creditStats = $this->creditApplicationRepository->getUserCreditStats($user->getEmail());
            
            $baseData = array_merge($baseData, [
                'approvedCredits' => $approvedCredits,
                'creditSubAccounts' => $creditSubAccounts,
                'creditStats' => $creditStats,
                'hasCredits' => !empty($approvedCredits) || !empty($activeLoans),
                'hasApplications' => !empty($this->creditApplicationRepository->findBy(['email' => $user->getEmail()])),
                'creditApplications' => $this->creditApplicationRepository->findBy(['email' => $user->getEmail()]),
            ]);
        }
        
        return array_merge($baseData, $additionalData);
    }
}
