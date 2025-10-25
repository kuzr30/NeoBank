<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\DemandeDevis;
use App\Entity\User;
use App\Enum\DemandeDevisStatusEnum;
use App\Repository\DemandeDevisRepository;
use App\Service\AssuranceSubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur d'administration pour les demandes de devis d'assurance
 */
#[Route('/admin/assurance-quotes', name: 'admin_assurance_quotes_')]
#[IsGranted('ROLE_ADMIN')]
class AssuranceQuotesController extends AbstractController
{
    public function __construct(
        private readonly DemandeDevisRepository $demandeDevisRepository,
        private readonly AssuranceSubscriptionService $subscriptionService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Liste des demandes de devis
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        // Critères de recherche
        $criteria = [
            'statut' => $request->query->get('statut'),
            'type_assurance' => $request->query->get('type_assurance'),
            'email' => $request->query->get('email'),
            'nom' => $request->query->get('nom'),
            'numero_devis' => $request->query->get('numero_devis'),
            'date_creation_from' => $request->query->get('date_creation_from'),
            'date_creation_to' => $request->query->get('date_creation_to'),
        ];

        // Filtrer les critères vides
        $criteria = array_filter($criteria, fn($value) => $value !== null && $value !== '');

        // Recherche avec critères
        $demandes = $this->demandeDevisRepository->searchDemandes($criteria);

        // Pagination manuelle (pour simplicité)
        $totalDemandes = count($demandes);
        $demandes = array_slice($demandes, $offset, $limit);

        // Statistiques
        $statistics = $this->demandeDevisRepository->getStatistics();

        return $this->render('admin/assurance_quotes/list.html.twig', [
            'demandes' => $demandes,
            'statistics' => $statistics,
            'current_page' => $page,
            'total_pages' => ceil($totalDemandes / $limit),
            'total_demandes' => $totalDemandes,
            'criteria' => array_merge([
                'statut' => '',
                'type_assurance' => '',
                'email' => '',
                'nom' => '',
                'numero_devis' => '',
                'date_creation_from' => '',
                'date_creation_to' => '',
            ], $criteria),
            'statuts' => DemandeDevisStatusEnum::cases(),
            'types_assurance' => \App\Enum\AssuranceType::cases(),
        ]);
    }

    /**
     * Détails d'une demande de devis
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(DemandeDevis $demandeDevis): Response
    {
        // Rechercher l'utilisateur correspondant
        $user = null;
        if ($demandeDevis->getEmail()) {
            $user = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $demandeDevis->getEmail()]);
        }

        // Vérifier s'il existe déjà un contrat
        $existingContract = null;
        if ($demandeDevis->getStatut() === DemandeDevisStatusEnum::CONVERTI) {
            $existingContract = $this->entityManager->getRepository(\App\Entity\ContratAssurance::class)
                ->findOneBy(['demandeDevis' => $demandeDevis]);
        }

        return $this->render('admin/assurance_quotes/show.html.twig', [
            'demande' => $demandeDevis,
            'user' => $user,
            'existing_contract' => $existingContract,
        ]);
    }

    /**
     * Approuve une demande de devis
     */
    #[Route('/{id}/approve', name: 'approve', methods: ['POST'])]
    public function approve(DemandeDevis $demandeDevis, Request $request): JsonResponse
    {
        try {
            if (!$demandeDevis->canBeApproved()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Cette demande ne peut pas être approuvée (statut: ' . $demandeDevis->getStatut()->value . ')'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Approuver la demande
            $demandeDevis->approve();
            $this->entityManager->persist($demandeDevis);

            // Créer automatiquement le contrat si un utilisateur existe
            $autoCreateContract = $request->request->getBoolean('auto_create_contract', true);
            $contractCreated = false;

            if ($autoCreateContract) {
                $user = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $demandeDevis->getEmail()]);

                if ($user) {
                    try {
                        $this->subscriptionService->createContractFromApprovedQuote($demandeDevis, $user);
                        $contractCreated = true;
                    } catch (\Exception $e) {
                        // Log l'erreur mais continue l'approbation
                        $this->addFlash('warning', 
                            'Demande approuvée mais erreur lors de la création du contrat: ' . $e->getMessage()
                        );
                    }
                }
            }

            $this->entityManager->flush();

            $message = 'Demande de devis approuvée avec succès.';
            if ($contractCreated) {
                $message .= ' Un contrat d\'assurance a été automatiquement créé.';
            } elseif ($autoCreateContract) {
                $message .= ' Aucun utilisateur trouvé pour créer automatiquement le contrat.';
            }

            return $this->json([
                'success' => true,
                'message' => $message,
                'contract_created' => $contractCreated,
                'new_status' => $demandeDevis->getStatut()->value
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'approbation: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Refuse une demande de devis
     */
    #[Route('/{id}/reject', name: 'reject', methods: ['POST'])]
    public function reject(DemandeDevis $demandeDevis, Request $request): JsonResponse
    {
        try {
            if (!$demandeDevis->canBeRejected()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Cette demande ne peut pas être refusée (statut: ' . $demandeDevis->getStatut()->value . ')'
                ], Response::HTTP_BAD_REQUEST);
            }

            $reason = $request->request->get('reason', '');
            
            $demandeDevis->reject($reason);
            $this->entityManager->persist($demandeDevis);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Demande de devis refusée.',
                'new_status' => $demandeDevis->getStatut()->value
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors du refus: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Marque une demande comme étant en cours de traitement
     */
    #[Route('/{id}/process', name: 'process', methods: ['POST'])]
    public function process(DemandeDevis $demandeDevis): JsonResponse
    {
        try {
            if ($demandeDevis->getStatut() !== DemandeDevisStatusEnum::EN_ATTENTE) {
                return $this->json([
                    'success' => false,
                    'message' => 'Seules les demandes en attente peuvent être mises en cours de traitement.'
                ], Response::HTTP_BAD_REQUEST);
            }

            $demandeDevis->setStatut(DemandeDevisStatusEnum::EN_COURS);
            $this->entityManager->persist($demandeDevis);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Demande mise en cours de traitement.',
                'new_status' => $demandeDevis->getStatut()->value
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crée manuellement un contrat pour une demande approuvée
     */
    #[Route('/{id}/create-contract', name: 'create_contract', methods: ['POST'])]
    public function createContract(DemandeDevis $demandeDevis, Request $request): JsonResponse
    {
        try {
            if ($demandeDevis->getStatut() !== DemandeDevisStatusEnum::APPROUVE) {
                return $this->json([
                    'success' => false,
                    'message' => 'Seules les demandes approuvées peuvent générer un contrat.'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Récupérer l'utilisateur
            $userId = $request->request->getInt('user_id');
            $user = null;

            if ($userId) {
                $user = $this->entityManager->getRepository(User::class)->find($userId);
            } else {
                // Rechercher par email
                $user = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $demandeDevis->getEmail()]);
            }

            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'Aucun utilisateur trouvé pour créer le contrat.'
                ], Response::HTTP_BAD_REQUEST);
            }

            $contrat = $this->subscriptionService->createContractFromApprovedQuote($demandeDevis, $user);

            return $this->json([
                'success' => true,
                'message' => 'Contrat créé avec succès.',
                'contract_number' => $contrat->getNumeroContrat()
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création du contrat: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Traitement en masse des demandes approuvées
     */
    #[Route('/batch/process-approved', name: 'batch_process_approved', methods: ['POST'])]
    public function batchProcessApproved(): JsonResponse
    {
        try {
            $results = $this->subscriptionService->processApprovedQuotesWithoutContracts();

            return $this->json([
                'success' => true,
                'message' => sprintf(
                    'Traitement terminé: %d contrats créés, %d erreurs',
                    $results['processed'],
                    $results['errors']
                ),
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors du traitement en masse: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Export des demandes au format CSV
     */
    #[Route('/export', name: 'export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        // Récupérer les critères de recherche
        $criteria = [
            'statut' => $request->query->get('statut'),
            'type_assurance' => $request->query->get('type_assurance'),
            'email' => $request->query->get('email'),
            'nom' => $request->query->get('nom'),
            'numero_devis' => $request->query->get('numero_devis'),
            'date_creation_from' => $request->query->get('date_creation_from'),
            'date_creation_to' => $request->query->get('date_creation_to'),
        ];

        $criteria = array_filter($criteria, fn($value) => $value !== null && $value !== '');
        $demandes = $this->demandeDevisRepository->searchDemandes($criteria);

        // Générer le CSV
        $csvContent = $this->generateCsvContent($demandes);

        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="demandes-devis-' . date('Y-m-d') . '.csv"');

        return $response;
    }

    /**
     * Génère le contenu CSV pour l'export
     */
    private function generateCsvContent(array $demandes): string
    {
        $output = fopen('php://temp', 'r+');

        // En-têtes CSV
        $headers = [
            'Numéro de devis',
            'Nom',
            'Prénom', 
            'Email',
            'Téléphone',
            'Type d\'assurance',
            'Statut',
            'Date de création',
            'Date de mise à jour',
            'Commentaires'
        ];

        fputcsv($output, $headers, ';');

        // Données
        foreach ($demandes as $demande) {
            $row = [
                $demande->getNumeroDevis(),
                $demande->getNom(),
                $demande->getPrenom(),
                $demande->getEmail(),
                $demande->getTelephone(),
                $demande->getTypeAssurance()->getLabel(),
                $demande->getStatut()->getLabel(),
                $demande->getCreatedAt()?->format('Y-m-d H:i:s'),
                $demande->getUpdatedAt()?->format('Y-m-d H:i:s'),
                $demande->getCommentaires() ?? ''
            ];

            fputcsv($output, $row, ';');
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }
}