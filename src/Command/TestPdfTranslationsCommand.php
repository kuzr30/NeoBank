<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:test-pdf-translations',
    description: 'Test les traductions PDF pour les contrats d\'assurance',
)]
class TestPdfTranslationsCommand extends Command
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('locale', InputArgument::OPTIONAL, 'Locale à tester (fr, nl, de)', 'fr');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $locale = $input->getArgument('locale');

        $io->title("Test des traductions PDF pour la locale: {$locale}");

        // Test des clés de traduction principales
        $keysToTest = [
            'title' => ['%contract_number%' => 'CONT-TEST-001'],
            'contract_title' => [],
            'company_info.title' => [],
            'company_info.establishment' => [],
            'company_info.contract_number' => ['%number%' => 'CONT-TEST-001'],
            'chapter_1.title' => [],
            'chapter_1.insurer.title' => [],
            'chapter_1.insurer.establishment' => [],
            'chapter_1.insurer.capital' => ['%capital%' => '1 000 000'],
            'chapter_1.insured.title' => [],
            'chapter_1.insured.residing' => [],
            'chapter_2.title' => [],
            'chapter_2.article_1.title' => [],
            'chapter_2.article_2.title' => [],
        ];

        $io->section('Test des traductions:');
        
        foreach ($keysToTest as $key => $parameters) {
            try {
                $translation = $this->translator->trans($key, $parameters, 'insurance_contract', $locale);
                
                if ($translation === $key) {
                    $io->error("❌ Clé non traduite: {$key}");
                } else {
                    $io->success("✅ {$key}: {$translation}");
                }
            } catch (\Exception $e) {
                $io->error("❌ Erreur pour {$key}: " . $e->getMessage());
            }
        }

        // Test avec le domaine de traduction par défaut
        $io->section('Test sans domaine spécifique:');
        $testKey = 'insurance_contract.title';
        $testTranslation = $this->translator->trans($testKey, ['%contract_number%' => 'TEST-001'], null, $locale);
        
        if ($testTranslation === $testKey) {
            $io->error("❌ Clé non traduite: {$testKey}");
        } else {
            $io->success("✅ {$testKey}: {$testTranslation}");
        }

        $io->success('Test terminé !');

        return Command::SUCCESS;
    }
}