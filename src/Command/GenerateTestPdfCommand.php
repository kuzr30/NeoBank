<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;
use App\Enum\AssuranceType;
use App\Enum\ContratAssuranceStatusEnum;
use App\Enum\DemandeDevisStatusEnum;
use App\Twig\CompanyVariablesExtension;

#[AsCommand(
    name: 'app:generate-test-pdf',
    description: 'Génère un PDF de test pour les contrats d\'assurance dans la langue spécifiée',
)]
class GenerateTestPdfCommand extends Command
{
    public function __construct(
        private Environment $twig,
        private CompanyVariablesExtension $companyVariablesExtension,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('locale', InputArgument::OPTIONAL, 'Langue du PDF (fr, nl, de)', 'fr');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $locale = $input->getArgument('locale');

        if (!in_array($locale, ['fr', 'nl', 'de'])) {
            $io->error("Langue non supportée: {$locale}. Langues disponibles: fr, nl, de");
            return Command::FAILURE;
        }

        $io->title("Génération d'un PDF de test en {$locale}");

        // Créer un objet demandeDevis de test
        $demandeDevis = (object) [
            'nom' => 'Test',
            'prenom' => 'Utilisateur',
            'email' => 'test.utilisateur@example.com',
            'telephone' => '+33 1 23 45 67 89',
            'typeAssurance' => AssuranceType::HABITATION,
            'numeroDevis' => 'DEV-' . date('Y') . '-' . str_pad((string) rand(100000, 999999), 6, '0', STR_PAD_LEFT),
        ];

        $user = (object) [
            'lastName' => 'Test',
            'firstName' => 'Utilisateur',
            'email' => 'test.utilisateur@example.com',
            'phone' => '+33 1 23 45 67 89',
            'address' => '123 Rue de Test, 75001 Paris, France',
            'birthDate' => new \DateTime('1980-01-01'),
        ];

        // Données complètes de test pour le contrat basées sur l'entité ContratAssurance
        $testData = [
            'contrat' => (object) [
                'numeroContrat' => 'HAB-' . date('Y') . '-' . str_pad((string) rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                'typeAssurance' => AssuranceType::HABITATION,
                'statut' => ContratAssuranceStatusEnum::ACTIF,
                'primeMensuelle' => '89.99',
                'montantCouverture' => '200000.00',
                'fraisAssurance' => '12.50',
                'fraisDossier' => '30.00',
                'dateActivation' => new \DateTimeImmutable(),
                'dateExpiration' => new \DateTimeImmutable('+1 year'),
                'dateResiliation' => null,
                'conditionsParticulieres' => 'Conditions particulières spécifiques à cette police d\'assurance.',
                'noteAdmin' => null,
                'createdAt' => new \DateTimeImmutable(),
                'updatedAt' => null,
                'dureeEnMois' => 12, // 1 an = 12 mois
                'demandeDevis' => $demandeDevis,
                'user' => $user,
            ],
            // Variables basées sur DemandeDevis
            'assure_nom' => 'Test',
            'assure_prenom' => 'Utilisateur',
            'assure_adresse' => '123 Rue de Test, 75001 Paris, France',
            'assure_email' => 'test.utilisateur@example.com',
            'assure_telephone' => '+33 1 23 45 67 89',
            'assure_date_naissance' => new \DateTime('1980-01-01'),
            'numero_devis' => 'DEV-' . date('Y') . '-' . str_pad((string) rand(100000, 999999), 6, '0', STR_PAD_LEFT),
            'company' => (object) [
                'name' => $this->companyVariablesExtension->getCompanyName(),
                'address' => $this->companyVariablesExtension->getCompanyAddress(),
                'email' => $this->companyVariablesExtension->getCompanyEmail(),
                'phone' => $this->companyVariablesExtension->getCompanyPhone(),
                'website' => $this->companyVariablesExtension->getCompanyWebsite(),
                'capital' => '1 000 000',
                'siren' => '123 456 789',
                'ape' => '6511Z',
                'logo_base64' => $this->companyVariablesExtension->getCompanyLogoBase64(),
            ],
            'expirationDate' => new \DateTime('+1 year'),
            '_locale' => $locale,
        ];

        try {
            $io->section('Rendu du template HTML...');
            
            // Rendu du template
            $htmlContent = $this->twig->render('pdf/insurance_contract.html.twig', $testData);
            
            $io->success('Template HTML rendu avec succès');
            
            $io->section('Génération du PDF...');
            
            // Configuration DOMPDF
            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);
            
            $dompdf = new Dompdf($options);
            $dompdf->setPaper('A4', 'portrait');
            
            // Génération du PDF
            $dompdf->loadHtml($htmlContent);
            $dompdf->render();
            $pdfContent = $dompdf->output();

            // Chemin de sauvegarde dans le dossier public/uploads/contracts
            $filename = 'test_insurance_contract_' . $locale . '_' . date('Ymd_His') . '.pdf';
            $contractDir = __DIR__ . '/../../public/uploads/contracts/';
            
            // S'assurer que le dossier existe
            if (!is_dir($contractDir)) {
                mkdir($contractDir, 0755, true);
            }
            
            $outputPath = $contractDir . $filename;
            
            file_put_contents($outputPath, $pdfContent);
            
            $io->success("PDF généré avec succès: {$outputPath}");
            
            // Informations sur le PDF généré
            $io->table(['Propriété', 'Valeur'], [
                ['Langue', $locale],
                ['Nom du fichier', $filename],
                ['Chemin complet', $outputPath],
                ['Taille', $this->formatBytes(strlen($pdfContent))],
                ['Nom de l\'entreprise', $testData['company']->name],
                ['Email de l\'entreprise', $testData['company']->email],
                ['Numéro de contrat', $testData['contrat']->numeroContrat],
                ['Type d\'assurance', $testData['contrat']->typeAssurance->getLabel()],
            ]);

            $io->note("Vous pouvez ouvrir le PDF avec: xdg-open {$outputPath}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la génération du PDF: ' . $e->getMessage());
            $io->block($e->getTraceAsString(), 'TRACE', 'fg=red');
            return Command::FAILURE;
        }
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}