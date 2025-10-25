<?php

namespace App\Controller;

use App\Enum\CreditTypeEnum;
use App\Enum\DurationUnitEnum;
use App\Service\ProfessionalTranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route([
    'fr' => '/{_locale}/simulation-credit',
    'nl' => '/{_locale}/krediet-simulatie',
    'en' => '/{_locale}/credit-simulation',
    'de' => '/{_locale}/kredit-simulation',
    'es' => '/{_locale}/simulacion-credito'
], requirements: ['_locale' => 'fr|nl|de|en|es'])]
final class CreditSimulationController extends AbstractController
{
    public function __construct(
        private readonly ProfessionalTranslationService $translationService
    ) {}

    #[Route('/', name: 'credit_simulation_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('credit_simulation/index.html.twig', [
            'creditTypes' => CreditTypeEnum::cases(),
            'durationUnits' => DurationUnitEnum::cases(),
        ]);
    }

    #[Route('/calculate', name: 'credit_simulation_calculate', methods: ['POST'])]
    public function calculate(Request $request): Response
    {
        try {
            // Récupération et validation des données
            $amount = (float) $request->request->get('amount');
            $duration = (int) $request->request->get('duration');
            $creditType = $request->request->get('creditType');
            
            // Validation basique
            $errors = $this->validateSimulationData($amount, $duration, $creditType);
            
            if (!empty($errors)) {
                return $this->json([
                    'success' => false,
                    'errors' => $errors
                ], 400);
            }
            
            // Calcul de la simulation
            $simulation = $this->calculateSimulation($amount, $duration, $creditType);
            
            return $this->json([
                'success' => true,
                'data' => [
                    'monthlyPayment' => $simulation['monthlyPayment'],
                    'totalCost' => $simulation['totalCost'],
                    'totalInterest' => $simulation['totalInterest'],
                    'interestRate' => $simulation['interestRate'],
                    'monthlyPaymentFormatted' => number_format($simulation['monthlyPayment'], 2, ',', ' ') . ' €',
                    'totalCostFormatted' => number_format($simulation['totalCost'], 2, ',', ' ') . ' €',
                    'totalInterestFormatted' => number_format($simulation['totalInterest'], 2, ',', ' ') . ' €'
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $this->translationService->trans('simulation.error.calculation_failed')
            ], 500);
        }
    }

    #[Route('/result', name: 'credit_simulation_result', methods: ['GET'])]
    public function result(Request $request): Response
    {
        // Récupération des paramètres depuis la query string
        $amount = $request->query->get('amount');
        $duration = $request->query->get('duration');
        $creditType = $request->query->get('creditType');
        
        if (!$amount || !$duration || !$creditType) {
            return $this->redirectToRoute('credit_simulation_index');
        }
        
        try {
            $simulation = $this->calculateSimulation(
                (float) $amount,
                (int) $duration,
                $creditType
            );
            
            return $this->render('credit_simulation/result.html.twig', [
                'simulation' => $simulation,
                'amount' => $amount,
                'duration' => $duration,
                'creditType' => $creditType
            ]);
            
        } catch (\Exception $e) {
            $this->addFlash('danger', $this->translationService->trans('simulation.error.calculation_failed'));
            return $this->redirectToRoute('credit_simulation_index');
        }
    }

    // ========== MÉTHODES UTILITAIRES PRIVÉES ==========

    /**
     * Validation des données de simulation
     */
    private function validateSimulationData(float $amount, int $duration, string $creditType): array
    {
        $errors = [];

        if ($amount < 3000 || $amount > 500000) {
            $errors['amount'] = $this->translationService->trans('simulation.errors.amount_range');
        }

        if ($duration < 12 || $duration > 360) {
            $errors['duration'] = $this->translationService->trans('simulation.errors.duration_range');
        }

        $validCreditTypes = array_column(CreditTypeEnum::cases(), 'value');
        if (!in_array($creditType, $validCreditTypes)) {
            $errors['creditType'] = $this->translationService->trans('simulation.errors.invalid_credit_type');
        }

        return $errors;
    }

    /**
     * Calcul de la simulation de crédit
     */
    private function calculateSimulation(float $amount, int $duration, string $creditType): array
    {
        // Taux d'intérêt selon le type de crédit
        $interestRates = [
            'personal' => 0.045, // 4.5%
            'auto' => 0.035,     // 3.5%
            'home' => 0.025,     // 2.5%
            'business' => 0.055, // 5.5%
        ];

        $annualRate = $interestRates[$creditType] ?? 0.045;
        $monthlyRate = $annualRate / 12;
        
        // Calcul de la mensualité (formule d'amortissement)
        if ($monthlyRate > 0) {
            $monthlyPayment = $amount * ($monthlyRate * pow(1 + $monthlyRate, $duration)) / (pow(1 + $monthlyRate, $duration) - 1);
        } else {
            $monthlyPayment = $amount / $duration;
        }
        
        $totalCost = $monthlyPayment * $duration;
        $totalInterest = $totalCost - $amount;

        return [
            'monthlyPayment' => round($monthlyPayment, 2),
            'totalCost' => round($totalCost, 2),
            'totalInterest' => round($totalInterest, 2),
            'interestRate' => $annualRate * 100,
            'amount' => $amount,
            'duration' => $duration,
            'creditType' => $creditType
        ];
    }

    #[Route([
        'fr' => '/tableau-amortissement',
        'nl' => '/aflossingstabel',
        'en' => '/amortization-table',
        'de' => '/tilgungsplan', 
        'es' => '/tabla-amortizacion'
    ], name: 'credit_simulation_amortization_table', methods: ['GET'])]
    public function amortizationTable(Request $request): Response
    {
        try {
            // Récupération des paramètres
            $amount = (float) $request->query->get('amount', 0);
            $duration = (int) $request->query->get('duration', 0);
            $creditType = $request->query->get('creditType', '');
            
            // Validation
            $errors = $this->validateSimulationData($amount, $duration, $creditType);
            
            if (!empty($errors)) {
                $this->addFlash('danger', $this->translationService->trans('credit_simulation.messages.invalid_parameters'));
                return $this->redirectToRoute('credit_simulation_index');
            }
            
            // Calcul du tableau d'amortissement
            $creditTypeEnum = CreditTypeEnum::from($creditType);
            $rate = $creditTypeEnum->getRate() / 100;
            $monthlyRate = $rate / 12;
            
            $monthlyPayment = $this->calculateMonthlyPayment($amount, $duration, $monthlyRate);
            $amortizationTable = $this->generateAmortizationTable($amount, $duration, $monthlyRate, $monthlyPayment);
            
            return $this->render('credit_simulation/amortization_table.html.twig', [
                'amount' => $amount,
                'duration' => $duration,
                'creditType' => $creditTypeEnum,
                'monthlyPayment' => $monthlyPayment,
                'totalCost' => $monthlyPayment * $duration,
                'totalInterest' => ($monthlyPayment * $duration) - $amount,
                'amortizationTable' => $amortizationTable,
                'rate' => $rate * 100
            ]);
            
        } catch (\Exception $e) {
            $this->addFlash('danger', $this->translationService->trans('credit_simulation.messages.calculation_error'));
            return $this->redirectToRoute('credit_simulation_index');
        }
    }
    /**
     * Calcul de la mensualité
     */
    private function calculateMonthlyPayment(float $amount, int $duration, float $monthlyRate): float
    {
        if ($monthlyRate == 0) {
            // Crédit sans intérêt
            return $amount / $duration;
        }
        
        // Formule standard de calcul de mensualité
        // M = C * (r * (1 + r)^n) / ((1 + r)^n - 1)
        // où M = mensualité, C = capital, r = taux mensuel, n = nombre de mois
        $factor = pow(1 + $monthlyRate, $duration);
        $monthlyPayment = $amount * ($monthlyRate * $factor) / ($factor - 1);
        
        return round($monthlyPayment, 2);
    }


    /**
     * Génération du tableau d'amortissement
     */
    private function generateAmortizationTable(float $amount, int $duration, float $monthlyRate, float $monthlyPayment): array
    {
        $table = [];
        $remainingCapital = $amount;
        
        for ($month = 1; $month <= $duration; $month++) {
            $interestPayment = $remainingCapital * $monthlyRate;
            $capitalPayment = $monthlyPayment - $interestPayment;
            $remainingCapital -= $capitalPayment;
            
            // Ajustement pour le dernier mois (éviter les valeurs négatives dues aux arrondis)
            if ($month === $duration) {
                $capitalPayment += $remainingCapital;
                $remainingCapital = 0;
                $monthlyPayment = $capitalPayment + $interestPayment;
            }
            
            $table[] = [
                'month' => $month,
                'monthlyPayment' => round($monthlyPayment, 2),
                'capitalPayment' => round($capitalPayment, 2),
                'interestPayment' => round($interestPayment, 2),
                'remainingCapital' => round(max(0, $remainingCapital), 2)
            ];
        }
        
        return $table;
    }

    /**
     * Récupération des choix de types de crédit traduits
     */
    private function getCreditTypeChoices(): array
    {
        $choices = [];
        foreach (CreditTypeEnum::cases() as $case) {
            $choices[$this->translationService->trans('credit_types.' . $case->value)] = $case->value;
        }
        return $choices;
    }

    /**
     * Récupération des choix d'unités de durée traduits
     */
    private function getDurationUnitChoices(): array
    {
        $choices = [];
        foreach (DurationUnitEnum::cases() as $case) {
            $choices[$this->translationService->trans('duration_units.' . $case->value)] = $case->value;
        }
        return $choices;
    }
}
