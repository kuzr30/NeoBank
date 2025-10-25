<?php

namespace App\Controller;

use App\Entity\ContractSubscription;
use App\Entity\User;
use App\Repository\ContractSubscriptionRepository;
use App\Repository\AccountRepository;
use App\Service\ContractSubscriptionService;
use App\Service\CardSubscriptionService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route([
    'fr' => '/{_locale}/contrats',
    'nl' => '/{_locale}/contracten', 
    'en' => '/{_locale}/contracts',
    'de' => '/{_locale}/vertrage',
    'es' => '/{_locale}/contratos'
], requirements: ['_locale' => 'fr|nl|de|en|es'])]
#[IsGranted('ROLE_CLIENT')]
class ContractSubscriptionController extends AbstractController
{
    public function __construct(
        private ContractSubscriptionRepository $contractRepository,
        private ContractSubscriptionService $contractService,
        private AccountRepository $accountRepository,
        private CardSubscriptionService $cardSubscriptionService,
        private LoggerInterface $logger
    ) {}

    /**
     * Page de signature de contrat
     */
    #[Route([
        'fr' => '/signature/{reference}',
        'nl' => '/handtekening/{reference}',
        'en' => '/signature/{reference}',
        'de' => '/unterschrift/{reference}',
        'es' => '/firma/{reference}'
    ], name: 'contract_signature', requirements: ['_locale' => 'fr|nl|de|en|es'], methods: ['GET'])]
    public function signature(string $reference): Response
    {
        $contract = $this->contractRepository->findByReference($reference);
        
        if (!$contract) {
            throw $this->createNotFoundException('Contrat non trouvé');
        }

        if (!$contract->canBeSigned()) {
            return $this->render('contract/expired.html.twig', [
                'contract' => $contract
            ]);
        }

        // Vérifier que la souscription est approuvée
        $subscription = $contract->getCardSubscription();
        if (!$subscription || $subscription->getStatus() !== 'approved') {
            return $this->render('contract/not_ready.html.twig', [
                'contract' => $contract,
                'subscription' => $subscription,
                'message' => 'Ce contrat ne peut pas encore être signé. La demande doit d\'abord être approuvée par notre équipe.'
            ]);
        }

        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer les données nécessaires pour le dashboard
        $accounts = $this->accountRepository->findActiveByUser($user);
        $userCardOrSubscription = $this->cardSubscriptionService->getUserCardOrSubscription($user);

        // Intégrer dans la structure du dashboard
        return $this->render('banking/dashboard.html.twig', [
            'contract' => $contract,
            'user' => $user,
            'accounts' => $accounts,
            'userCardOrSubscription' => $userCardOrSubscription,
            'activeTab' => 'contract-signature',
        ]);
    }

    /**
     * Traitement de la signature électronique
     */
    #[Route([
        'fr' => '/signature/{reference}/signer',
        'nl' => '/handtekening/{reference}/ondertekenen',
        'en' => '/signature/{reference}/sign',
        'de' => '/unterschrift/{reference}/unterschreiben',
        'es' => '/firma/{reference}/firmar'
    ], name: 'contract_sign_process', requirements: ['_locale' => 'fr|nl|de|en|es'], methods: ['POST'])]
    public function processSignature(string $reference, Request $request): JsonResponse
    {
        $contract = $this->contractRepository->findByReference($reference);
        
        if (!$contract) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Contrat non trouvé'
            ], 404);
        }

        if (!$contract->canBeSigned()) {
            $this->logger->debug('Contract cannot be signed', [
                'reference' => $reference,
                'status' => $contract->getStatus(),
                'isSent' => $contract->isSent(),
                'isExpired' => $contract->isExpired(),
                'isSigned' => $contract->isSigned(),
                'expiresAt' => $contract->getExpiresAt()?->format('Y-m-d H:i:s')
            ]);
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce contrat ne peut plus être signé (expiré ou déjà signé)'
            ], 400);
        }

        // Vérifier que la souscription est approuvée
        $subscription = $contract->getCardSubscription();
        if (!$subscription || $subscription->getStatus() !== 'approved') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce contrat ne peut pas encore être signé. La demande doit d\'abord être approuvée.'
            ], 400);
        }

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('sign_contract_' . $reference, $request->request->get('_token'))) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Token de sécurité invalide'
            ], 400);
        }

        // Récupération des données de signature
        $signatureData = $request->request->get('signature');
        $acceptTerms = $request->request->get('accept_terms');

        if (!$signatureData || !$acceptTerms) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Signature et acceptation des conditions requises'
            ], 400);
        }

        try {
            // Traiter la signature
            $this->contractService->signContract(
                $contract,
                $signatureData,
                $request->getClientIp() ?? 'unknown',
                $request->headers->get('User-Agent') ?? 'unknown'
            );

            // Mettre à jour le statut de la souscription
            $this->cardSubscriptionService->processContractSigned($subscription);

            return new JsonResponse([
                'success' => true,
                'message' => 'Contrat signé avec succès ! Votre paiement sera traité dans les plus brefs délais.',
                'redirectUrl' => $this->generateUrl('contract_signature_success', ['reference' => $reference])
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la signature : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Page de confirmation de signature
     */
    #[Route([
        'fr' => '/signature/{reference}/succes',
        'nl' => '/handtekening/{reference}/succes',
        'en' => '/signature/{reference}/success',
        'de' => '/unterschrift/{reference}/erfolg',
        'es' => '/firma/{reference}/exito'
    ], name: 'contract_signature_success', requirements: ['_locale' => 'fr|nl|de|en|es'], methods: ['GET'])]
    public function signatureSuccess(string $reference): Response
    {
        $contract = $this->contractRepository->findByReference($reference);
        
        if (!$contract || !$contract->isSigned()) {
            return $this->redirectToRoute('contract_signature', ['reference' => $reference]);
        }

        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer les données du dashboard
        $accounts = $this->accountRepository->findActiveByUser($user);
        $account = !empty($accounts) ? $accounts[0] : null;
        $cardSubscriptions = $this->cardSubscriptionService->getUserCardOrSubscription($user);

        return $this->render('banking/dashboard.html.twig', [
            'activeTab' => 'contract-success',
            'contract' => $contract,
            'account' => $account,
            'cardSubscriptions' => $cardSubscriptions,
            'user' => $user,
        ]);
    }

    /**
     * Téléchargement du contrat PDF
     */
    #[Route([
        'fr' => '/carte/telecharger/{reference}',
        'nl' => '/kaart/downloaden/{reference}',
        'en' => '/card/download/{reference}',
        'de' => '/karte/herunterladen/{reference}',
        'es' => '/tarjeta/descargar/{reference}'
    ], name: 'card_contract_download', requirements: ['_locale' => 'fr|nl|de|en|es'], methods: ['GET'])]
    public function downloadContract(string $reference): Response
    {
        $contract = $this->contractRepository->findByReference($reference);
        
        if (!$contract) {
            throw $this->createNotFoundException('Contrat non trouvé');
        }

        $pdfPath = $contract->getContractPdfPath();
        if (!$pdfPath || !file_exists($this->getParameter('kernel.project_dir') . '/public/' . $pdfPath)) {
            throw $this->createNotFoundException('Fichier PDF non trouvé');
        }

        $fullPath = $this->getParameter('kernel.project_dir') . '/public/' . $pdfPath;
        $filename = 'Contrat_' . $contract->getReference() . '.pdf';

        return $this->file($fullPath, $filename);
    }
}
