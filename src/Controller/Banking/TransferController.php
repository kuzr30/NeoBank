<?php

namespace App\Controller\Banking;

use App\Entity\User;
use App\Entity\BankAccount;
use App\Entity\Transfer;
use App\Form\TransferType;
use App\Form\TransferCodeValidationType;
use App\Service\TransferManager;
use App\Service\KycService;
use App\Service\ProfessionalTranslationService;
use App\Repository\AccountRepository;
use App\Repository\BankAccountRepository;
use App\Repository\TransferRepository;
use App\Repository\TransactionRepository;
use App\Repository\LoanRepository;
use App\Controller\Banking\Trait\KycAccessTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;

#[Route([
    'fr' => '/{_locale}/banking/virements',
    'nl' => '/{_locale}/banking/overboekingen',
    'en' => '/{_locale}/banking/transfers',
    'de' => '/{_locale}/banking/uberweisungen',
    'es' => '/{_locale}/banking/transferencias'
], requirements: ['_locale' => 'fr|nl|de|en|es'])]
#[IsGranted('ROLE_CLIENT')]
class TransferController extends AbstractController
{
    use KycAccessTrait;

    public function __construct(
        private TransferManager $transferManager,
        private AccountRepository $accountRepository,
        private BankAccountRepository $bankAccountRepository,
        private TransferRepository $transferRepository,
        private TransactionRepository $transactionRepository,
        private LoanRepository $loanRepository,
        private EntityManagerInterface $entityManager,
        private KycService $kycService,
        private ProfessionalTranslationService $translationService
    ) {
    }

    #[Route('', name: 'banking_transfers')]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier l'accès KYC
        if (!$this->checkKycAccess($user)) {
            return $this->redirectToRoute('kyc_index');
        }

        // Vérifier si l'utilisateur est bloqué
        if ($this->transferManager->isUserBlocked($user)) {
            $this->addFlash('danger', 
                $this->translationService->tp('flash.account_blocked_transfers', [], 'banking_transfer_controller')
            );
            return $this->redirectToRoute('banking_dashboard');
        }

        // Récupérer les comptes enregistrés (RIB)
        $bankAccounts = $this->bankAccountRepository->findBy([
            'user' => $user,
            'isActive' => true
        ]);

        // Récupérer les comptes de l'utilisateur pour la sidebar
        $userAccounts = $this->accountRepository->findActiveByUser($user);
        
        // Ajouter les variations mensuelles pour la sidebar
        foreach ($userAccounts as $account) {
            $monthlyChange = $this->transactionRepository->getMonthlyBalanceChange($account);
            $account->monthlyBalanceChange = $monthlyChange;
        }

        // Récupérer les virements en cours
        $activeTransfers = $this->transferRepository->findActiveByUser($user);

        // Si c'est une requête AJAX, retourner seulement le contenu de l'onglet
        if ($request->isXmlHttpRequest()) {
            return $this->render('banking/tabs/transfers.html.twig', [
                'bank_accounts' => $bankAccounts,
                'active_transfers' => $activeTransfers,
                'user' => $user,
            ]);
        }

        // Sinon, retourner le dashboard complet avec les comptes pour la sidebar
        return $this->render('banking/dashboard.html.twig', $this->getDashboardData($user, 'transfers', [
            'bank_accounts' => $bankAccounts,
            'active_transfers' => $activeTransfers,
        ]));
    }

    #[Route([
        'fr' => '/nouveau',
        'nl' => '/nieuw',
        'en' => '/new',
        'de' => '/neu',
        'es' => '/nuevo'
    ], name: 'banking_transfer_new')]
    public function new(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier l'accès KYC
        if (!$this->checkKycAccess($user)) {
            return $this->redirectToRoute('kyc_index');
        }

        // Vérifier si l'utilisateur est bloqué
        if ($this->transferManager->isUserBlocked($user)) {
            $this->addFlash('danger', 
                $this->translationService->tp('flash.account_blocked_transfers', [], 'banking_transfer_controller')
            );
            return $this->redirectToRoute('banking_transfers');
        }

        // Récupérer les comptes bancaires disponibles (destinations)
        $bankAccounts = $this->bankAccountRepository->findBy([
            'user' => $user,
            'isActive' => true
        ]);

        if (empty($bankAccounts)) {
            $this->addFlash('warning', 
                $this->translationService->tp('flash.register_beneficiary_first', [], 'banking_transfer_controller')
            );
            return $this->redirectToRoute('banking_ribs');
        }

        // Récupérer les comptes de l'utilisateur (sources) - SAME as BankingController
        $userAccounts = $this->accountRepository->findActiveByUser($user);

        // Ajouter les variations mensuelles pour chaque compte (comme dans BankingController)
        foreach ($userAccounts as $account) {
            $monthlyChange = $this->transactionRepository->getMonthlyBalanceChange($account);
            $account->monthlyBalanceChange = $monthlyChange;
        }

        if (empty($userAccounts)) {
            $this->addFlash('danger', 
                $this->translationService->tp('flash.no_source_account', [], 'banking_transfer_controller')
            );
            return $this->redirectToRoute('banking_dashboard');
        }

        $form = $this->createForm(TransferType::class, null, [
            'bank_accounts' => $bankAccounts
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $form->getData();
                
                $transfer = $this->transferManager->initiateTransfer(
                    $user,
                    $data['destinationAccount'],
                    $data['amount'],
                    $data['description']
                );

                $this->addFlash('success', 
                    $this->translationService->tp('flash.transfer_created_success', [], 'banking_transfer_controller')
                );

                return $this->redirectToRoute('banking_transfer_validate', [
                    'id' => $transfer->getId()
                ]);

            } catch (\Exception $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('banking/tabs/transfer_new.html.twig', [
                'form' => $form,
                'bank_accounts' => $bankAccounts,
                'user_accounts' => $userAccounts,
                'user' => $user,
            ]);
        }

        return $this->render('banking/dashboard.html.twig', $this->getDashboardData($user, 'transfer_new', [
            'form' => $form,
            'bank_accounts' => $bankAccounts,
            'user_accounts' => $userAccounts,
        ]));
    }

    #[Route([
        'fr' => '/{id}/valider',
        'nl' => '/{id}/valideren',
        'en' => '/{id}/validate',
        'de' => '/{id}/validieren',
        'es' => '/{id}/validar'
    ], name: 'banking_transfer_validate', requirements: ['id' => '\d+'])]
    public function validate(Request $request, Transfer $transfer): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que ce virement appartient à l'utilisateur
        if ($transfer->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        // Vérifier l'état du virement
        if (!in_array($transfer->getStatus(), ['pending', 'executing'])) {
            $this->addFlash('info', 
                $this->translationService->tp('flash.transfer_no_longer_pending', [], 'banking_transfer_controller')
            );
            return $this->redirectToRoute('banking_transfers');
        }

        // Vérifier si le compte est bloqué
        if ($transfer->isAccountBlocked()) {
            $this->addFlash('danger', 
                $this->translationService->tp('flash.account_blocked', [], 'banking_transfer_controller')
            );
            return $this->redirectToRoute('banking_transfers');
        }

        // Obtenir le code actuel à valider
        $currentCode = $transfer->getCurrentCode();
        
        if (!$currentCode) {
            return $this->redirectToRoute('banking_transfers');
        }

        $form = $this->createForm(TransferCodeValidationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $inputCode = $form->get('code')->getData();

            try {
                $result = $this->transferManager->validateTransferCode($transfer, $inputCode);

                if ($result['success']) {
                    $this->addFlash('success', 
                        $this->translationService->tp('flash.transfer_validation_success', [], 'banking_transfer_controller')
                    );

                    // Vérifier si le virement doit être exécuté immédiatement
                    if ($result['transfer_status'] === 'executing') {
                        // Dans ce système, le virement est "exécuté" mais restera en attente du prochain code
                        $this->addFlash('info', 
                            $this->translationService->tp('flash.transfer_in_progress', [], 'banking_transfer_controller')
                        );
                    }

                } else {
                    $this->addFlash('danger', 
                        $this->translationService->tp('flash.invalid_validation_code', [], 'banking_transfer_controller')
                    );

                    if ($result['account_blocked']) {
                        return $this->redirectToRoute('banking_transfers');
                    }
                }

            } catch (\Exception $e) {
                $this->addFlash('danger', 
                    $this->translationService->tp('flash.validation_error', ['%error%' => $e->getMessage()], 'banking_transfer_controller')
                );
            }

            // Recharger le virement pour avoir les données à jour
            $this->entityManager->refresh($transfer);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('banking/tabs/transfer_validate.html.twig', [
                'transfer' => $transfer,
                'current_code' => $currentCode,
                'form' => $form,
                'user' => $user,
            ]);
        }

        return $this->render('banking/dashboard.html.twig', array_merge(
            $this->getDashboardData($user, 'transfer_validate'),
            [
                'transfer' => $transfer,
                'current_code' => $currentCode,
                'form' => $form,
            ]
        ));
    }

    #[Route([
        'fr' => '/{id}/details',
        'nl' => '/{id}/details',
        'en' => '/{id}/details',
        'de' => '/{id}/details',
        'es' => '/{id}/detalles'
    ], name: 'banking_transfer_details', requirements: ['id' => '\d+'])]
    public function details(Transfer $transfer): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que ce virement appartient à l'utilisateur
        if ($transfer->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        // Récupérer les comptes de l'utilisateur pour la sidebar
        $userAccounts = $this->accountRepository->findActiveByUser($user);
        
        // Ajouter les variations mensuelles pour la sidebar
        foreach ($userAccounts as $account) {
            $monthlyChange = $this->transactionRepository->getMonthlyBalanceChange($account);
            $account->monthlyBalanceChange = $monthlyChange;
        }

        return $this->render('banking/dashboard.html.twig', $this->getDashboardData($user, 'transfer_details', [
            'transfer' => $transfer,
        ]));
    }

    #[Route([
        'fr' => '/{id}/annuler',
        'nl' => '/{id}/annuleren',
        'en' => '/{id}/cancel',
        'de' => '/{id}/stornieren',
        'es' => '/{id}/cancelar'
    ], name: 'banking_transfer_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancel(Request $request, Transfer $transfer): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que ce virement appartient à l'utilisateur
        if ($transfer->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('cancel_transfer_' . $transfer->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 
                $this->translationService->tp('flash.invalid_security_token', [], 'banking_transfer_controller')
            );
            return $this->redirectToRoute('banking_transfers');
        }

        // Vérifier que le virement peut être annulé
        if (!in_array($transfer->getStatus(), ['pending', 'executing'])) {
            $this->addFlash('danger', 
                $this->translationService->tp('flash.transfer_cannot_be_cancelled', [], 'banking_transfer_controller')
            );
            return $this->redirectToRoute('banking_transfers');
        }

        try {
            $transfer->setStatus('cancelled');
            $this->entityManager->flush();

            $this->addFlash('success', 
                $this->translationService->tp('flash.transfer_cancelled_success', [], 'banking_transfer_controller')
            );

        } catch (\Exception $e) {
            $this->addFlash('danger', 
                $this->translationService->tp('flash.cancellation_error', ['%error%' => $e->getMessage()], 'banking_transfer_controller')
            );
        }

        return $this->redirectToRoute('banking_transfers');
    }

    /**
     * Vérifie l'accès KYC et redirige si nécessaire
     */
    private function checkKycAccess(User $user): bool
    {
        if (!$this->kycService->canUserAccessBanking($user)) {
            $this->addFlash('warning', 
                $this->translationService->tp('flash.kyc_required_transfers', [], 'banking_transfer_controller')
            );
            return false;
        }
        return true;
    }

    /**
     * Prépare toutes les données nécessaires pour le dashboard
     */
    private function getDashboardData(User $user, string $activeTab = 'transfers', array $additionalData = []): array
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
        
        // Récupération des prêts actifs
        $activeLoans = [];
        foreach ($accounts as $account) {
            $loans = $this->loanRepository->findByAccount($account);
            $activeLoans = array_merge($activeLoans, array_filter($loans, fn($loan) => $loan->getStatus() === 'active'));
        }
        
        // Calcul du solde total
        $totalBalance = $this->accountRepository->getTotalBalanceForUser($user);
        
        return array_merge([
            'user' => $user,
            'accounts' => $accounts,
            'recent_transactions' => $recentTransactions,
            'active_loans' => $activeLoans,
            'total_balance' => $totalBalance,
            'activeTab' => $activeTab,
        ], $additionalData);
    }
}
