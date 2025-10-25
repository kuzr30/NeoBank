<?php

namespace App\Controller\Banking;

use App\Entity\User;
use App\Entity\BankAccount;
use App\Manager\RibManager;
use App\Service\KycService;
use App\Form\BankAccountType;
use App\Service\ProfessionalTranslationService;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Controller\Banking\Trait\KycAccessTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route([
    'fr' => '/{_locale}/banking/mes-beneficiaires',
    'nl' => '/{_locale}/banking/mijn-begunstigden',
    'en' => '/{_locale}/banking/my-beneficiaries',
    'de' => '/{_locale}/banking/meine-begunstigten',
    'es' => '/{_locale}/banking/mis-beneficiarios'
], requirements: ['_locale' => 'fr|nl|de|en|es'])]
#[IsGranted('ROLE_CLIENT')]
class RibController extends AbstractController
{
    use KycAccessTrait;
    
    public function __construct(
        private RibManager $ribManager,
        private KycService $kycService,
        private ProfessionalTranslationService $translationService
    ) {
    }

    #[Route('', name: 'banking_ribs_index')]
    public function index(Request $request): Response
    {
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer les RIBs de l'utilisateur via le manager
        $ribs = $this->ribManager->getUserRibs($user);
        
        // Si c'est une requête AJAX, retourner seulement le contenu de l'onglet
        if ($request->isXmlHttpRequest()) {
            return $this->render('banking/tabs/ribs.html.twig', [
                'ribs' => $ribs,
                'bank_accounts' => $ribs, // Pour compatibilité avec le template
                'user' => $user,
            ]);
        }
        
        return $this->render('banking/dashboard.html.twig', array_merge(
            $this->ribManager->getDashboardData($user, 'ribs'),
            [
                'ribs' => $ribs,
                'bank_accounts' => $ribs, // Pour compatibilité avec le template
            ]
        ));
    }

    #[Route([
        'fr' => '/nouveau',
        'nl' => '/nieuw',
        'en' => '/new',
        'de' => '/neu',
        'es' => '/nuevo'
    ], name: 'banking_rib_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }
        
        /** @var User $user */
        $user = $this->getUser();
        
        $bankAccount = new BankAccount();
        $bankAccount->setUser($user);

        $form = $this->createForm(BankAccountType::class, $bankAccount);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Vérifier si l'IBAN existe déjà pour cet utilisateur (même si le formulaire n'est pas valide)
            if ($bankAccount->getIban() && $this->ribManager->ibanExistsForUser($user, $bankAccount->getIban())) {
                // Ajouter l'erreur au champ IBAN pour l'affichage en rouge sous l'input
                $form->get('iban')->addError(new FormError(
                    $this->translationService->tp('form_error.iban_duplicate', [], 'banking_rib_controller')
                ));
                $this->addFlash('danger', 
                    $this->translationService->tp('flash.iban_already_exists', [], 'banking_rib_controller')
                );
            } elseif ($form->isValid()) {
                // Créer le RIB via le manager seulement si le formulaire est valide ET l'IBAN n'existe pas
                $this->ribManager->createRib($bankAccount);
                $this->addFlash('success', 
                    $this->translationService->tp('flash.rib_added_success', [], 'banking_rib_controller')
                );

                return $this->redirectToRoute('banking_ribs_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        // Récupérer les RIBs existants pour l'affichage
        $ribs = $this->ribManager->getUserRibs($user);

        return $this->render('banking/dashboard.html.twig', array_merge(
            $this->ribManager->getDashboardData($user, 'rib_new'),
            [
                'ribs' => $ribs,
                'bank_accounts' => $ribs, // Pour compatibilité avec le template
                'form' => $form,
            ]
        ));
    }

    #[Route([
        'fr' => '/{id}',
        'nl' => '/{id}',
        'en' => '/{id}',
        'de' => '/{id}',
        'es' => '/{id}'
    ], name: 'banking_rib_show', methods: ['GET'])]
    public function show(BankAccount $bankAccount): Response
    {
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est propriétaire de ce RIB
        if ($bankAccount->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }
        
        return $this->render('banking/dashboard.html.twig', array_merge(
            $this->ribManager->getDashboardData($user, 'rib_show'),
            [
                'bank_account' => $bankAccount,
            ]
        ));
    }

    #[Route([
        'fr' => '/{id}/supprimer',
        'nl' => '/{id}/verwijderen',
        'en' => '/{id}/delete',
        'de' => '/{id}/loschen',
        'es' => '/{id}/eliminar'
    ], name: 'banking_rib_delete', methods: ['POST'])]
    public function delete(Request $request, BankAccount $bankAccount): Response
    {
        // Vérification KYC
        if ($kycCheck = $this->checkKycAccess()) {
            return $kycCheck;
        }
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est propriétaire de ce RIB
        if ($bankAccount->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }
        
        if ($this->isCsrfTokenValid('delete'.$bankAccount->getId(), $request->getPayload()->getString('_token'))) {
            // Supprimer le RIB via le manager
            $this->ribManager->deleteRib($bankAccount);
            $this->addFlash('success', 
                $this->translationService->tp('flash.rib_deleted_success', [], 'banking_rib_controller')
            );
        }
        
        return $this->redirectToRoute('banking_ribs_index', [], Response::HTTP_SEE_OTHER);
    }
}