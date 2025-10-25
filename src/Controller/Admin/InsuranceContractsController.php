<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ContratAssurance;
use App\Enum\ContratAssuranceStatusEnum;
use App\Repository\ContratAssuranceRepository;
use App\Service\AssuranceSubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur d'administration pour les contrats d'assurance
 */
#[Route('/admin/insurance-contracts', name: 'admin_insurance_contracts_')]
#[IsGranted('ROLE_ADMIN')]
class InsuranceContractsController extends AbstractController
{
    public function __construct(
        private readonly ContratAssuranceRepository $contratRepository,
        private readonly AssuranceSubscriptionService $subscriptionService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Liste des contrats d'assurance
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;

        // Critères de recherche
        $criteria = [
            'statut' => $request->query->get('statut'),
            'type_assurance' => $request->query->get('type_assurance'),
            'numero_contrat' => $request->query->get('numero_contrat'),
            'user_email' => $request->query->get('user_email'),
            'user_nom' => $request->query->get('user_nom'),
            'date_creation_from' => $request->query->get('date_creation_from'),
            'date_creation_to' => $request->query->get('date_creation_to'),
            'date_expiration_from' => $request->query->get('date_expiration_from'),
            'date_expiration_to' => $request->query->get('date_expiration_to'),
        ];

        // Filtrer les critères vides
        $criteria = array_filter($criteria, fn($value) => $value !== null && $value !== '');

        // Recherche avec pagination
        $contrats = $this->contratRepository->search($criteria, $page, $limit);
        $totalContrats = $this->contratRepository->countSearch($criteria);

        // Statistiques
        $statistics = $this->contratRepository->getStatistics();

        return $this->render('admin/insurance_contracts/list.html.twig', [
            'contrats' => $contrats,
            'statistics' => $statistics,
            'current_page' => $page,
            'total_pages' => ceil($totalContrats / $limit),
            'total_contrats' => $totalContrats,
            'criteria' => array_merge([
                'statut' => '',
                'type_assurance' => '',
                'numero_contrat' => '',
                'user_email' => '',
                'user_nom' => '',
                'date_creation_from' => '',
                'date_creation_to' => '',
                'date_expiration_from' => '',
                'date_expiration_to' => '',
            ], $criteria),
            'statuts' => ContratAssuranceStatusEnum::cases(),
            'types_assurance' => \App\Enum\AssuranceType::cases(),
        ]);
    }

    /**
     * Détails d'un contrat d'assurance
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(ContratAssurance $contrat): Response
    {
        return $this->render('admin/insurance_contracts/show.html.twig', [
            'contrat' => $contrat,
        ]);
    }

    /**
     * Suspend un contrat d'assurance
     */
    #[Route('/{id}/suspend', name: 'suspend', methods: ['POST'])]
    public function suspend(ContratAssurance $contrat, Request $request): JsonResponse
    {
        try {
            $reason = $request->request->get('reason', '');
            
            $this->subscriptionService->suspendContract($contrat, $reason);

            return $this->json([
                'success' => true,
                'message' => 'Contrat suspendu avec succès.',
                'new_status' => $contrat->getStatut()->value
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suspension: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Réactive un contrat suspendu
     */
    #[Route('/{id}/reactivate', name: 'reactivate', methods: ['POST'])]
    public function reactivate(ContratAssurance $contrat): JsonResponse
    {
        try {
            $this->subscriptionService->reactivateContract($contrat);

            return $this->json([
                'success' => true,
                'message' => 'Contrat réactivé avec succès.',
                'new_status' => $contrat->getStatut()->value
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la réactivation: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Résilie un contrat d'assurance
     */
    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(ContratAssurance $contrat, Request $request): JsonResponse
    {
        try {
            $reason = $request->request->get('reason', '');
            
            $this->subscriptionService->cancelContract($contrat, $reason);

            return $this->json([
                'success' => true,
                'message' => 'Contrat résilié avec succès.',
                'new_status' => $contrat->getStatut()->value
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la résiliation: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Met à jour les termes du contrat
     */
    #[Route('/{id}/update-terms', name: 'update_terms', methods: ['POST'])]
    public function updateTerms(ContratAssurance $contrat, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (isset($data['prime_mensuelle'])) {
                $contrat->setPrimeMensuelle($data['prime_mensuelle']);
            }

            if (isset($data['prime_annuelle'])) {
                $contrat->setPrimeAnnuelle($data['prime_annuelle']);
            }

            if (isset($data['franchise'])) {
                $contrat->setFranchise($data['franchise']);
            }

            if (isset($data['montant_couverture'])) {
                $contrat->setMontantCouverture($data['montant_couverture']);
            }

            if (isset($data['date_fin_prevue'])) {
                $contrat->setDateFinPrevue(new \DateTimeImmutable($data['date_fin_prevue']));
            }

            if (isset($data['prochaine_echeance'])) {
                $contrat->setProchaineEcheance(new \DateTimeImmutable($data['prochaine_echeance']));
            }

            $this->entityManager->persist($contrat);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Termes du contrat mis à jour avec succès.'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Renouvelle automatiquement un contrat
     */
    #[Route('/{id}/renew', name: 'renew', methods: ['POST'])]
    public function renew(ContratAssurance $contrat, Request $request): JsonResponse
    {
        try {
            if (!$contrat->canBeRenewed()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Ce contrat ne peut pas être renouvelé (statut: ' . $contrat->getStatut()->value . ')'
                ], Response::HTTP_BAD_REQUEST);
            }

            $renewalPeriod = $request->request->getInt('renewal_period', 12); // mois
            
            $contrat->renew($renewalPeriod);
            $this->entityManager->persist($contrat);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Contrat renouvelé avec succès pour ' . $renewalPeriod . ' mois.',
                'new_expiration_date' => $contrat->getDateFinPrevue()?->format('Y-m-d')
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors du renouvellement: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Export des contrats au format CSV
     */
    #[Route('/export', name: 'export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        // Récupérer les critères de recherche
        $criteria = [
            'statut' => $request->query->get('statut'),
            'type_assurance' => $request->query->get('type_assurance'),
            'numero_contrat' => $request->query->get('numero_contrat'),
            'user_email' => $request->query->get('user_email'),
            'user_nom' => $request->query->get('user_nom'),
            'date_creation_from' => $request->query->get('date_creation_from'),
            'date_creation_to' => $request->query->get('date_creation_to'),
            'date_expiration_from' => $request->query->get('date_expiration_from'),
            'date_expiration_to' => $request->query->get('date_expiration_to'),
        ];

        $criteria = array_filter($criteria, fn($value) => $value !== null && $value !== '');
        $contrats = $this->contratRepository->search($criteria);

        // Générer le CSV
        $csvContent = $this->generateCsvContent($contrats);

        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="contrats-assurance-' . date('Y-m-d') . '.csv"');

        return $response;
    }

    /**
     * Statistiques avancées
     */
    #[Route('/analytics/dashboard', name: 'analytics', methods: ['GET'])]
    public function analytics(): Response
    {
        $statistics = $this->contratRepository->getStatistics();
        $revenueByType = $this->contratRepository->getRevenueByType();
        $expiringContracts = $this->contratRepository->getExpiringContracts(30); // 30 prochains jours
        $monthlyStats = $this->contratRepository->getMonthlyStatistics();

        return $this->render('admin/insurance_contracts/analytics.html.twig', [
            'statistics' => $statistics,
            'revenue_by_type' => $revenueByType,
            'expiring_contracts' => $expiringContracts,
            'monthly_stats' => $monthlyStats,
        ]);
    }

    /**
     * Traite les contrats expirant bientôt
     */
    #[Route('/batch/process-expiring', name: 'batch_process_expiring', methods: ['POST'])]
    public function batchProcessExpiring(Request $request): JsonResponse
    {
        try {
            $days = $request->request->getInt('days', 30);
            $action = $request->request->get('action', 'notify'); // notify, auto_renew, expire

            $expiringContracts = $this->contratRepository->getExpiringContracts($days);
            $results = [
                'processed' => 0,
                'errors' => 0,
                'details' => []
            ];

            foreach ($expiringContracts as $contrat) {
                try {
                    switch ($action) {
                        case 'auto_renew':
                            if ($contrat->canBeRenewed()) {
                                $contrat->renew(12); // Renouvellement de 12 mois
                                $this->entityManager->persist($contrat);
                                $results['details'][] = [
                                    'contract_number' => $contrat->getNumeroContrat(),
                                    'action' => 'renewed',
                                    'status' => 'success'
                                ];
                            }
                            break;

                        case 'expire':
                            if ($contrat->canExpire()) {
                                $contrat->expire();
                                $this->entityManager->persist($contrat);
                                $results['details'][] = [
                                    'contract_number' => $contrat->getNumeroContrat(),
                                    'action' => 'expired',
                                    'status' => 'success'
                                ];
                            }
                            break;

                        case 'notify':
                        default:
                            // Ici vous pourriez envoyer une notification
                            $results['details'][] = [
                                'contract_number' => $contrat->getNumeroContrat(),
                                'action' => 'notified',
                                'status' => 'success'
                            ];
                            break;
                    }

                    $results['processed']++;

                } catch (\Exception $e) {
                    $results['errors']++;
                    $results['details'][] = [
                        'contract_number' => $contrat->getNumeroContrat(),
                        'action' => $action,
                        'status' => 'error',
                        'error' => $e->getMessage()
                    ];
                }
            }

            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => sprintf(
                    'Traitement terminé: %d contrats traités, %d erreurs',
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
     * Génère le contenu CSV pour l'export
     */
    private function generateCsvContent(array $contrats): string
    {
        $output = fopen('php://temp', 'r+');

        // En-têtes CSV
        $headers = [
            'Numéro de contrat',
            'Utilisateur',
            'Email',
            'Type d\'assurance',
            'Statut',
            'Prime mensuelle',
            'Prime annuelle',
            'Franchise',
            'Montant de couverture',
            'Date de début',
            'Date de fin prévue',
            'Prochaine échéance',
            'Date de création',
            'Date de mise à jour'
        ];

        fputcsv($output, $headers, ';');

        // Données
        foreach ($contrats as $contrat) {
            $user = $contrat->getUser();
            $row = [
                $contrat->getNumeroContrat(),
                $user ? $user->getNom() . ' ' . $user->getPrenom() : '',
                $user ? $user->getEmail() : '',
                $contrat->getTypeAssurance()->getLabel(),
                $contrat->getStatut()->getLabel(),
                $contrat->getPrimeMensuelle() ?? '',
                $contrat->getPrimeAnnuelle() ?? '',
                $contrat->getFranchise() ?? '',
                $contrat->getMontantCouverture() ?? '',
                $contrat->getDateDebut()?->format('Y-m-d'),
                $contrat->getDateFinPrevue()?->format('Y-m-d'),
                $contrat->getProchaineEcheance()?->format('Y-m-d'),
                $contrat->getCreatedAt()?->format('Y-m-d H:i:s'),
                $contrat->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];

            fputcsv($output, $row, ';');
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }
}