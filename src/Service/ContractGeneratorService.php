<?php

namespace App\Service;

use App\Entity\CreditApplication;
use App\Entity\ContratAssurance;
use App\Repository\CompanySettingsRepository;
use App\Service\AmortizationService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContractGeneratorService
{
    public function __construct(
        private Environment $twig,
        private CompanySettingsRepository $companySettingsRepository,
        private AmortizationService $amortizationService,
        private TranslatorInterface $translator
    ) {}

    public function generateCreditContract(CreditApplication $creditApplication, ?string $locale = null): string
    {
        // Configuration DOMPDF
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);

        // Récupération des informations de la société
        $companySettings = $this->companySettingsRepository->getCompanySettings();
        
        // Récupération du tableau d'amortissement existant
        $amortizationTable = $this->amortizationService->getAmortizationTableFromDatabase($creditApplication);

        // Récupération des frais de dossier
        $contractFees = $creditApplication->getContractFees();
        $fraisDossier = null;
        
        // Chercher les frais de dossier spécifiquement
        foreach ($contractFees as $fee) {
            if (stripos($fee->getName(), 'dossier') !== false || stripos($fee->getName(), 'frais') !== false) {
                $fraisDossier = $fee;
                break;
            }
        }

        // Déterminer la locale à utiliser
        $currentLocale = $locale ?? 'fr';
        
        // Sauvegarder la locale actuelle du translator
        $originalLocale = $this->translator->getLocale();
        
        // Définir la locale pour les traductions
        $this->translator->setLocale($currentLocale);

        try {
            // Génération du HTML à partir du template Twig avec la locale appropriée
            $html = $this->twig->render('pdf/credit_contract.html.twig', [
                'creditApplication' => $creditApplication,
                'simulation' => $creditApplication, // Alias pour compatibilité template
                'contract' => [
                    'contractNumber' => $this->generateContractNumber($creditApplication)
                ],
                'company' => [
                    'name' => $companySettings->getCompanyName(),
                    'address' => $companySettings->getAddress(),
                    'phone' => $companySettings->getPhone(),
                    'email' => $companySettings->getEmail(),
                    'logo_base64' => $companySettings->getLogoBase64()
                ],
                'amortizationTable' => $amortizationTable,
                'fraisDossier' => $fraisDossier,
                'contractFees' => $contractFees,
                '_locale' => $currentLocale
            ]);
        } finally {
            // Restaurer la locale originale
            $this->translator->setLocale($originalLocale);
        }

        // Conversion en PDF
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function generateContractNumber(CreditApplication $creditApplication): string
    {
        return sprintf(
            'CREDIT-%s-%06d',
            date('Y'),
            $creditApplication->getId()
        );
    }

    public function getContractFilename(CreditApplication $creditApplication): string
    {
        return sprintf(
            'contrat_credit_%s_%s_%s.pdf',
            $this->generateContractNumber($creditApplication),
            date('Ymd'),
            date('His')
        );
    }

    public function generateInsuranceContract(ContratAssurance $contratAssurance, ?string $locale = null): string
    {
        // Configuration DOMPDF
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);

        // Définir la locale (celle passée en paramètre ou la locale courante)
        $originalLocale = $this->translator->getLocale();
        $currentLocale = $locale ?? $originalLocale;
        
        // Définir temporairement la locale si différente
        if ($locale && $locale !== $originalLocale) {
            $this->translator->setLocale($locale);
        }

        // Récupération des informations de la société
        $companySettings = $this->companySettingsRepository->getCompanySettings();

        // Calcul de la date d'expiration basée sur le crédit associé si existe
        $expirationDate = $this->calculateInsuranceExpirationDate($contratAssurance);

        // Génération du HTML à partir du template Twig
        $html = $this->twig->render('pdf/insurance_contract.html.twig', [
            'contrat' => $contratAssurance,
            'expirationDate' => $expirationDate,
            'company' => [
                'name' => $companySettings->getCompanyName(),
                'address' => $companySettings->getAddress(),
                'postal_code' => '75001', // Valeur par défaut
                'city' => 'Paris', // Valeur par défaut
                'phone' => $companySettings->getPhone(),
                'email' => $companySettings->getEmail(),
                'siren' => $companySettings->getSiret() ?? '123 456 789', // Utilise SIRET ou valeur par défaut
                'ape' => '6511Z', // Code APE par défaut pour les assurances
                'capital' => '1 000 000', // Capital par défaut
                'logo_base64' => $companySettings->getLogoBase64()
            ],
            '_locale' => $currentLocale
        ]);

        // Conversion en PDF
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Restaurer la locale originale si elle a été modifiée
        if ($locale && $locale !== $originalLocale) {
            $this->translator->setLocale($originalLocale);
        }

        return $dompdf->output();
    }

    private function calculateInsuranceExpirationDate(ContratAssurance $contratAssurance): ?\DateTimeInterface
    {
        // Récupérer l'association crédit-assurance via la demande de devis
        $demandeDevis = $contratAssurance->getDemandeDevis();
        if (!$demandeDevis) {
            return $contratAssurance->getDateExpiration();
        }

        // Chercher s'il y a une demande de crédit avec le même email
        $userEmail = $contratAssurance->getUser()->getEmail();
        
        // Utiliser le repository pour trouver la demande de crédit la plus récente pour cet email
        $entityManager = $this->companySettingsRepository->getEntityManager();
        $creditApplicationRepo = $entityManager->getRepository(\App\Entity\CreditApplication::class);
        
        $creditApplication = $creditApplicationRepo->createQueryBuilder('ca')
            ->where('ca.email = :email')
            ->andWhere('ca.status IN (:validStatuses)')
            ->setParameter('email', $userEmail)
            ->setParameter('validStatuses', ['pending', 'approved', 'contract_validated', 'disbursed'])
            ->orderBy('ca.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$creditApplication) {
            return $contratAssurance->getDateExpiration();
        }

        // Récupérer la date du dernier prélèvement dans AmortizationSchedule
        $amortizationRepo = $entityManager->getRepository(\App\Entity\AmortizationSchedule::class);
        
        $lastPayment = $amortizationRepo->createQueryBuilder('am')
            ->where('am.creditApplication = :creditApplication')
            ->setParameter('creditApplication', $creditApplication)
            ->orderBy('am.paymentDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastPayment && $lastPayment->getPaymentDate()) {
            return $lastPayment->getPaymentDate();
        }

        // Si pas de tableau d'amortissement, retourner la date d'expiration existante
        return $contratAssurance->getDateExpiration();
    }

    public function getInsuranceContractFilename(ContratAssurance $contratAssurance): string
    {
        return sprintf(
            'contrat_assurance_%s_%s_%s.pdf',
            $contratAssurance->getNumeroContrat(),
            date('Ymd'),
            date('His')
        );
    }
}
