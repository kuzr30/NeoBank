<?php

namespace App\Controller\Admin;

use App\Entity\Transfer;
use App\Entity\TransferCode;
use App\Entity\User;
use App\Form\Admin\TransferCodeType;
use App\Service\TransferManager;
use App\Repository\TransferRepository;
use App\Repository\TransferCodeRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/admin/transfers')]
#[IsGranted('ROLE_ADMIN')]
class AdminTransferController extends AbstractController
{
    public function __construct(
        private TransferManager $transferManager,
        private TransferRepository $transferRepository,
        private TransferCodeRepository $transferCodeRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'admin_transfers')]
    public function index(): Response
    {
        // Récupérer tous les virements en attente
        $pendingTransfers = $this->transferRepository->findPendingForAdmin();

        // Récupérer les utilisateurs bloqués
        $blockedUsers = $this->userRepository->createQueryBuilder('u')
            ->leftJoin('u.transfers', 't')
            ->andWhere('t.isAccountBlocked = :blocked')
            ->setParameter('blocked', true)
            ->groupBy('u.id')
            ->getQuery()
            ->getResult();

        return $this->render('admin/transfers/index.html.twig', [
            'pending_transfers' => $pendingTransfers,
            'blocked_users' => $blockedUsers,
        ]);
    }

    #[Route('/{id}', name: 'admin_transfer_show', requirements: ['id' => '\d+'])]
    public function show(Transfer $transfer): Response
    {
        return $this->render('admin/transfers/show.html.twig', [
            'transfer' => $transfer,
        ]);
    }

    #[Route('/{id}/add-code', name: 'admin_transfer_add_code', requirements: ['id' => '\d+'])]
    public function addCode(Request $request, Transfer $transfer): Response
    {
        // Vérifier que le virement peut recevoir des codes
        if (!in_array($transfer->getStatus(), ['pending', 'executing'])) {
            $this->addFlash('danger', 'Ce virement ne peut plus recevoir de codes.');
            return $this->redirectToRoute('admin_transfer_show', ['id' => $transfer->getId()]);
        }

        $form = $this->createForm(TransferCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $form->getData();
                
                $transferCode = $this->transferManager->addCodeToTransfer(
                    $transfer,
                    $data['codeName'],
                    $data['codeValue']
                );

                $this->addFlash('success', sprintf(
                    'Code "%s" ajouté avec succès au virement (ordre %d).',
                    $transferCode->getCodeName(),
                    $transferCode->getCodeOrder()
                ));

                return $this->redirectToRoute('admin_transfer_show', ['id' => $transfer->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de l\'ajout du code : ' . $e->getMessage());
            }
        }

        return $this->render('admin/transfers/add_code.html.twig', [
            'transfer' => $transfer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/generate-code', name: 'admin_transfer_generate_code', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function generateCode(Request $request, Transfer $transfer): Response
    {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('generate_code_' . $transfer->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_transfer_show', ['id' => $transfer->getId()]);
        }

        try {
            $codeName = $request->request->get('code_name', 'Code généré automatiquement');
            $generatedCode = TransferCode::generateRandomCode();
            
            $transferCode = $this->transferManager->addCodeToTransfer(
                $transfer,
                $codeName,
                $generatedCode
            );

            $this->addFlash('success', sprintf(
                'Code généré automatiquement : "%s" (%s)',
                $generatedCode,
                $transferCode->getCodeName()
            ));

        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la génération du code : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_transfer_show', ['id' => $transfer->getId()]);
    }

    #[Route('/{id}/cancel', name: 'admin_transfer_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancel(Request $request, Transfer $transfer): Response
    {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('cancel_transfer_' . $transfer->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_transfer_show', ['id' => $transfer->getId()]);
        }

        try {
            $transfer->setStatus('cancelled');
            $this->entityManager->flush();

            $this->addFlash('success', 'Virement annulé par l\'administration.');

        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'annulation : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_transfers');
    }

    #[Route('/{id}/complete', name: 'admin_transfer_complete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function complete(Request $request, Transfer $transfer): Response
    {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('complete_transfer_' . $transfer->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_transfer_show', ['id' => $transfer->getId()]);
        }

        try {
            // Exécuter le virement physiquement
            $this->transferManager->executeTransfer($transfer);

            $this->addFlash('success', sprintf(
                'Virement de %.2f € exécuté avec succès.',
                (float) $transfer->getAmount()
            ));

        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'exécution : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_transfers');
    }

    #[Route('/user/{id}/unblock', name: 'admin_user_unblock', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function unblockUser(Request $request, User $user): Response
    {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('unblock_user_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_transfers');
        }

        try {
            $this->transferManager->unblockUser($user);

            $this->addFlash('success', sprintf(
                'Utilisateur %s %s débloqué avec succès.',
                $user->getFirstName(),
                $user->getLastName()
            ));

        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du déblocage : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_transfers');
    }

    #[Route('/codes/expired', name: 'admin_transfers_expired_codes')]
    public function expiredCodes(): Response
    {
        $expiredCodes = $this->transferCodeRepository->findExpiredCodes();

        return $this->render('admin/transfers/expired_codes.html.twig', [
            'expired_codes' => $expiredCodes,
        ]);
    }

    #[Route('/process-expired', name: 'admin_transfers_process_expired', methods: ['POST'])]
    public function processExpired(Request $request): Response
    {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('process_expired', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_transfers');
        }

        try {
            $this->transferManager->processExpiredCodes();
            $this->addFlash('success', 'Codes expirés traités avec succès.');

        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du traitement : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_transfers');
    }

    #[Route('/statistics', name: 'admin_transfers_statistics')]
    public function statistics(): Response
    {
        // Statistiques diverses pour l'admin
        $stats = [
            'pending_transfers' => $this->transferRepository->count(['status' => 'pending']),
            'executing_transfers' => $this->transferRepository->count(['status' => 'executing']),
            'completed_transfers' => $this->transferRepository->count(['status' => 'completed']),
            'blocked_users' => $this->entityManager->createQuery(
                'SELECT COUNT(DISTINCT t.user) FROM App\Entity\Transfer t WHERE t.isAccountBlocked = :blocked'
            )->setParameter('blocked', true)->getSingleScalarResult(),
            'expired_codes' => $this->transferCodeRepository->createQueryBuilder('tc')
                ->select('COUNT(tc.id)')
                ->andWhere('tc.status = :status')
                ->andWhere('tc.expiresAt < :now')
                ->setParameter('status', 'validated')
                ->setParameter('now', new \DateTimeImmutable())
                ->getQuery()
                ->getSingleScalarResult(),
        ];

        return $this->render('admin/transfers/statistics.html.twig', [
            'stats' => $stats,
        ]);
    }
}
