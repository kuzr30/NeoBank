<?php

namespace App\Controller\Banking;

use App\Entity\User;
use App\Entity\SubAccountCredit;
use App\Service\KycService;
use App\Manager\TransferManager;
use App\Service\ProfessionalTranslationService;
use App\Controller\Banking\Trait\KycAccessTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route([
    'fr' => '/{_locale}/banking/virement-credit',
    'nl' => '/{_locale}/banking/krediet-overboeking',
    'en' => '/{_locale}/banking/credit-transfer',
    'de' => '/{_locale}/banking/kredit-uberweisung',
    'es' => '/{_locale}/banking/transferencia-credito'
], requirements: ['_locale' => 'fr|nl|de|en|es'])]
#[IsGranted('ROLE_CLIENT')]
class CreditTransferController extends AbstractController
{
    use KycAccessTrait;
    
    public function __construct(
        private TransferManager $transferManager,
        private KycService $kycService,
        private EntityManagerInterface $entityManager,
        private ProfessionalTranslationService $translationService
    ) {
    }

    #[Route('/form/{id}', name: 'banking_credit_transfer_form')]
    public function transferForm(SubAccountCredit $subAccountCredit): Response
    {
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur peut accéder à ce sous-compte
        if (!$this->transferManager->canAccessSubAccountCredit($user, $subAccountCredit)) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('banking/dashboard.html.twig', array_merge(
            $this->transferManager->getDashboardData($user, 'credit_transfer_form'),
            [
                'subAccountCredit' => $subAccountCredit,
            ]
        ));
    }

    #[Route('/initiate', name: 'banking_credit_transfer_initiate', methods: ['POST'])]
    public function initiateTransfer(Request $request): Response
    {
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }

        // Validation CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('credit_transfer', $token)) {
            $this->addFlash('danger', 
                $this->translationService->tp('flash.invalid_security_token', [], 'banking_credit_transfer_controller')
            );
            return $this->redirectToRoute('banking_credits');
        }
        
        /** @var User $user */
        $user = $this->getUser();
        
        $subAccountId = $request->request->get('sub_account_id');
        $amount = $request->request->get('amount');
        
        // Récupérer le sous-compte crédit
        $subAccountCredit = $this->entityManager->getRepository(SubAccountCredit::class)->find($subAccountId);
        
        if (!$subAccountCredit || 
            !$subAccountCredit->getAccount() || 
            $subAccountCredit->getAccount()->getOwner() !== $this->getUser()) {
            $this->addFlash('danger', 
                $this->translationService->tp('flash.sub_account_not_found', [], 'banking_credit_transfer_controller')
            );
            return $this->redirectToRoute('banking_credits');
        }
        
        // Valider le montant
        $errors = $this->transferManager->validateTransferAmount($subAccountCredit, $amount);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('danger', $error);
            }
            return $this->redirectToRoute('banking_credit_transfer_form', ['id' => $subAccountId]);
        }

        // Initier le transfert et générer le code
        $verificationCode = $this->transferManager->initiateTransfer(
            $request->getSession(), 
            $subAccountId, 
            $amount,
            $user
        );

        // Message de confirmation
        $this->addFlash('success', 
            $this->translationService->tp('flash.verification_code_sent', [], 'banking_credit_transfer_controller')
        );

        return $this->render('banking/dashboard.html.twig', array_merge(
            $this->transferManager->getDashboardData($user, 'credit_transfer_confirm'),
            [
                'subAccountCredit' => $subAccountCredit,
                'amount' => $amount,
            ]
        ));
    }

    #[Route('/confirm', name: 'banking_credit_transfer_confirm', methods: ['POST'])]
    public function confirmTransfer(Request $request): Response
    {
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }

        // Validation CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('credit_transfer_confirm', $token)) {
            $this->addFlash('danger', 
                $this->translationService->tp('flash.invalid_security_token', [], 'banking_credit_transfer_controller')
            );
            return $this->redirectToRoute('banking_credits');
        }
        
        /** @var User $user */
        $user = $this->getUser();
        
        $verificationCode = $request->request->get('verification_code');
        
        // Vérifier le code de vérification
        $result = $this->transferManager->verifyTransferCode($request->getSession(), $verificationCode);
        
        if (isset($result['error'])) {
            $this->addFlash('danger', $result['error']);
            return $this->redirectToRoute('banking_credits');
        }

        try {
            // Effectuer le transfert
            $this->transferManager->executeCreditTransfer(
                $result['transfer']['sub_account_id'], 
                $result['transfer']['amount'], 
                $user
            );
            
            // Nettoyer la session
            $this->transferManager->clearPendingCreditTransfer($request->getSession());
            
            $this->addFlash('success', 
                $this->translationService->tp('flash.transfer_success', [], 'banking_credit_transfer_controller')
            );
            
        } catch (\Exception $e) {
            $this->addFlash('danger', 
                $this->translationService->tp('flash.transfer_error', [], 'banking_credit_transfer_controller') . $e->getMessage()
            );
        }

        return $this->redirectToRoute('banking_credits');
    }
}