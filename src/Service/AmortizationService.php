<?php

namespace App\Service;

use App\Entity\CreditApplication;
use App\Entity\AmortizationSchedule;
use App\Repository\AmortizationScheduleRepository;
use Doctrine\ORM\EntityManagerInterface;

class AmortizationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AmortizationScheduleRepository $amortizationScheduleRepository
    ) {
    }

    /**
     * Génère et sauvegarde le tableau d'amortissement pour une demande de crédit
     */
    public function generateAndSaveAmortizationSchedule(CreditApplication $creditApplication): void
    {
        // Supprimer l'éventuel tableau existant
        $this->amortizationScheduleRepository->removeAllByCreditApplication($creditApplication);
        
        // Générer les données du tableau
        $schedule = $this->generateAmortizationTable($creditApplication);
        
        // Sauvegarder chaque échéance
        foreach ($schedule as $payment) {
            $amortizationEntry = new AmortizationSchedule();
            $amortizationEntry->setCreditApplication($creditApplication);
            $amortizationEntry->setMonth($payment['month']);
            $amortizationEntry->setPaymentDate($payment['date']);
            $amortizationEntry->setMonthlyPayment((string) $payment['monthly_payment']);
            $amortizationEntry->setPrincipalPayment((string) $payment['principal_payment']);
            $amortizationEntry->setInterestPayment((string) $payment['interest_payment']);
            $amortizationEntry->setRemainingAmount((string) $payment['remaining_amount']);
            
            $this->entityManager->persist($amortizationEntry);
        }
        
        $this->entityManager->flush();
    }

    /**
     * Génère le tableau d'amortissement sous forme de tableau
     */
    private function generateAmortizationTable(CreditApplication $creditApplication): array
    {
        $table = [];
        $remainingAmount = (float) $creditApplication->getLoanAmount();
        $monthlyPayment = (float) $creditApplication->getMonthlyPayment();
        $interestRate = 0.0145 / 12; // Taux d'exemple 1.45% annuel -> mensuel
        $termMonths = $creditApplication->getDuration();
        
        // Si la durée est en années, convertir en mois
        if ($creditApplication->getDurationUnit()->value === 'years') {
            $termMonths *= 12;
        }
        
        for ($month = 1; $month <= $termMonths; $month++) {
            $interestPayment = $remainingAmount * $interestRate;
            $principalPayment = $monthlyPayment - $interestPayment;
            
            // S'assurer que le capital ne dépasse pas le montant restant
            if ($principalPayment > $remainingAmount) {
                $principalPayment = $remainingAmount;
                $monthlyPayment = $principalPayment + $interestPayment;
            }
            
            $table[] = [
                'month' => $month,
                'monthly_payment' => $monthlyPayment,
                'principal_payment' => $principalPayment,
                'interest_payment' => $interestPayment,
                'remaining_amount' => $remainingAmount - $principalPayment,
                'date' => (clone $creditApplication->getCreatedAt())->modify("+{$month} months")
            ];
            
            $remainingAmount -= $principalPayment;
            
            // Arrêter si le prêt est remboursé
            if ($remainingAmount <= 0) {
                break;
            }
        }
        
        return $table;
    }

    /**
     * Récupère le tableau d'amortissement pour une demande de crédit depuis la base
     *
     * @return array Le tableau formaté pour l'affichage
     */
    public function getAmortizationTableFromDatabase(CreditApplication $creditApplication): array
    {
        $schedules = $this->amortizationScheduleRepository->findByCreditApplication($creditApplication);
        
        $table = [];
        foreach ($schedules as $schedule) {
            $table[] = [
                'month' => $schedule->getMonth(),
                'monthly_payment' => (float) $schedule->getMonthlyPayment(),
                'principal_payment' => (float) $schedule->getPrincipalPayment(),
                'interest_payment' => (float) $schedule->getInterestPayment(),
                'remaining_amount' => (float) $schedule->getRemainingAmount(),
                'date' => $schedule->getPaymentDate()
            ];
        }
        
        return $table;
    }

    /**
     * Vérifie si un tableau d'amortissement existe pour cette demande
     */
    public function hasAmortizationSchedule(CreditApplication $creditApplication): bool
    {
        return $this->amortizationScheduleRepository->countByCreditApplication($creditApplication) > 0;
    }
}
