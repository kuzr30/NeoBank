<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\DemandeDevis;
use App\Entity\User;
use App\Enum\AssuranceType;
use App\Form\DemandeDevisType;
use App\Message\DevisEmailMessage;
use App\Repository\DemandeDevisRepository;
use App\Repository\UserRepository;
use App\Service\ProfessionalTranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{_locale}/services', name: 'services_', requirements: ['_locale' => 'fr|nl|de|en|es'])]
class ServicesController extends AbstractController
{
    public function __construct(
        private readonly DemandeDevisRepository $demandeDevisRepository,
        private readonly UserRepository $userRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly ProfessionalTranslationService $translationService
    ) {}

    #[Route([
        'fr' => '/comptes-cartes',
        'nl' => '/rekeningen-kaarten',
        'en' => '/accounts-cards',
        'de' => '/konten-karten',
        'es' => '/cuentas-tarjetas'
    ], name: 'accounts_cards', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function accountsCards(): Response
    {
        return $this->render('services/accounts_cards.html.twig', [
            'page_title' => $this->translationService->trans('services.accounts_cards.page.title', [], 'services'),
            'meta_description' => $this->translationService->trans('services.accounts_cards.page.description', [], 'services'),
        ]);
    }

    #[Route([
        'fr' => '/epargne-placements',
        'nl' => '/sparen-beleggingen',
        'en' => '/savings-investments',
        'de' => '/sparen-investitionen',
        'es' => '/ahorros-inversiones'
    ], name: 'savings_investments', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function savingsInvestments(): Response
    {
        return $this->render('services/savings_investments.html.twig', [
            'page_title' => $this->translationService->trans('savings_investments.meta.title', [], 'savings_investments:'),
            'meta_description' => $this->translationService->trans('savings_investments.page.description', [], 'services'),
        ]);
    }

    #[Route([
        'fr' => '/assurances',
        'nl' => '/verzekeringen',
        'en' => '/insurances',
        'de' => '/versicherungen',
        'es' => '/seguros'
    ], name: 'insurances', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function insurances(): Response
    {
        return $this->render('services/insurances.html.twig', [
            'page_title' => $this->translationService->trans('insurance.page.title', [], 'insurance'),
            'meta_description' => $this->translationService->trans('insurance.page.description', [], 'insurance'),
            'types_assurance' => AssuranceType::cases()
        ]);
    }

    #[Route([
        'fr' => '/assurances/devis/{type}',
        'nl' => '/verzekeringen/offerte/{type}',
        'en' => '/insurances/quote/{type}',
        'de' => '/versicherungen/angebot/{type}',
        'es' => '/seguros/cotizacion/{type}'
    ], name: 'devis_form', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function devisForm(string $type, Request $request): Response
    {
        $assuranceType = AssuranceType::tryFrom($type);
        
        if (!$assuranceType instanceof AssuranceType) {
            throw $this->createNotFoundException($this->translationService->trans('services.devis.error.type_not_found', [], 'services'));
        }

        $demande = new DemandeDevis();
        $demande->setTypeAssurance($assuranceType); // Pré-sélectionner le type

        $form = $this->createForm(DemandeDevisType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Enregistrer la demande en base
                $this->demandeDevisRepository->save($demande, true);
                
                // Déterminer la langue pour l'email
                $emailLocale = $this->determineUserLanguage($demande->getEmail(), $request->getLocale());
                
                // Redirection immédiate après enregistrement
                $this->addFlash('success', $this->translationService->trans(
                    'services.devis.success.message',
                    ['numero' => $demande->getNumeroDevis()],
                    'services'
                ));

                // Dispatch du message pour l'envoi d'email asynchrone APRÈS la redirection
                try {
                    $this->messageBus->dispatch(new DevisEmailMessage($demande->getId(), $emailLocale));
                } catch (\Exception $emailException) {
                    // Log l'erreur d'email mais ne pas empêcher la redirection
                    error_log('Erreur envoi email devis: ' . $emailException->getMessage());
                }

                return $this->redirectToRoute('services_devis_confirmation', [
                    'numeroDevis' => $demande->getNumeroDevis(),
                    '_locale' => $request->getLocale()
                ]);

            } catch (\Exception $e) {
                $this->addFlash('danger', $this->translationService->trans('services.devis.error.save_failed', [], 'services'));
                $this->addFlash('danger', 'Erreur: ' . $e->getMessage());
            }
        } elseif ($form->isSubmitted()) {
            // Debug: afficher les erreurs de validation
            $this->addFlash('danger', $this->translationService->trans('services.devis.error.validation_failed', [], 'services'));
            
            // Log les erreurs pour debug
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('debug', $this->translationService->trans(
                    'services.devis.error.validation_error',
                    ['message' => $error->getMessage()],
                    'services'
                ));
            }
        }

        return $this->render('services/devis_form.html.twig', [
            'form' => $form,
            'assuranceType' => $assuranceType,
            'page_title' => $this->translationService->trans(
                'services.devis.page.title', 
                ['type' => $this->translationService->trans($assuranceType->getLabel(), [], 'enums')], 
                'services'
            ),
            'meta_description' => $this->translationService->trans(
                'services.devis.page.description', 
                [
                    'type' => $this->translationService->trans($assuranceType->getLabel(), [], 'enums'), 
                    'description' => $this->translationService->trans($assuranceType->getDescription(), [], 'enums')
                ], 
                'services'
            )
        ]);
    }



    #[Route([
        'fr' => '/assurances/devis/confirmation/{numeroDevis}',
        'nl' => '/verzekeringen/offerte/bevestiging/{numeroDevis}',
        'en' => '/insurances/quote/confirmation/{numeroDevis}',
        'de' => '/versicherungen/angebot/bestaetigung/{numeroDevis}',
        'es' => '/seguros/cotizacion/confirmacion/{numeroDevis}'
    ], name: 'devis_confirmation', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function devisConfirmation(string $numeroDevis): Response
    {
        $demande = $this->demandeDevisRepository->findByNumeroDevis($numeroDevis);
        
        if (!$demande instanceof DemandeDevis) {
            throw $this->createNotFoundException($this->translationService->trans('services.devis.error.not_found', [], 'services'));
        }

        return $this->render('services/devis_confirmation.html.twig', [
            'demande' => $demande,
            'page_title' => $this->translationService->trans(
                'services.devis.confirmation.page.title', 
                ['numero' => $numeroDevis], 
                'services'
            ),
            'meta_description' => $this->translationService->trans('services.devis.confirmation.page.description', [], 'services'),
        ]);
    }

    /**
     * Détermine la langue à utiliser pour l'email en fonction de l'utilisateur
     */
    private function determineUserLanguage(string $email, string $fallbackLocale): string
    {
        // Chercher si l'utilisateur existe déjà dans la base
        $user = $this->userRepository->findOneBy(['email' => $email]);
        
        if ($user instanceof User && $user->getLanguage()) {
            // L'utilisateur existe et a une langue définie
            return $user->getLanguage();
        }
        
        // Sinon, utiliser la langue de l'URL comme fallback
        return $fallbackLocale;
    }
}
