<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:replace-company-phone',
    description: 'Remplace les numéros de téléphone (+33 1 23 45 67 89 et 01 23 45 67 89) par "%company_phone%" dans tous les fichiers de traduction',
)]
class ReplaceCompanyPhoneCommand extends Command
{
    private const PHONE_PATTERNS = [
        '+33 1 23 45 67 89',
        '01 23 45 67 89',
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Remplacement des numéros de téléphone dans les fichiers de traduction');
        
        $translationsDir = $this->projectDir . '/translations';
        
        if (!is_dir($translationsDir)) {
            $io->error('Le répertoire translations n\'existe pas.');
            return Command::FAILURE;
        }
        
        // Trouve tous les fichiers YAML dans le dossier translations
        $finder = new Finder();
        $finder->files()->in($translationsDir)->name('*.yaml');
        
        $filesProcessed = 0;
        $filesModified = 0;
        $totalReplacements = 0;
        $replacementsByPattern = [];
        
        // Initialise les compteurs par pattern
        foreach (self::PHONE_PATTERNS as $pattern) {
            $replacementsByPattern[$pattern] = 0;
        }
        
        foreach ($finder as $file) {
            $filesProcessed++;
            $filePath = $file->getRealPath();
            $content = file_get_contents($filePath);
            $originalContent = $content;
            $fileReplacements = 0;
            
            // Remplace chaque pattern
            foreach (self::PHONE_PATTERNS as $pattern) {
                $count = substr_count($content, $pattern);
                
                if ($count > 0) {
                    $content = str_replace($pattern, '%company_phone%', $content);
                    $replacementsByPattern[$pattern] += $count;
                    $fileReplacements += $count;
                }
            }
            
            // Sauvegarde le fichier seulement si des modifications ont été faites
            if ($content !== $originalContent) {
                file_put_contents($filePath, $content);
                $filesModified++;
                $totalReplacements += $fileReplacements;
                
                $io->writeln(sprintf(
                    '<info>✓</info> %s (<comment>%d remplacement(s)</comment>)',
                    $file->getRelativePathname(),
                    $fileReplacements
                ));
            }
        }
        
        $io->newLine();
        $io->success('Traitement terminé !');
        $io->writeln([
            sprintf('Fichiers analysés : <info>%d</info>', $filesProcessed),
            sprintf('Fichiers modifiés : <info>%d</info>', $filesModified),
            sprintf('Remplacements effectués : <info>%d</info>', $totalReplacements),
        ]);
        
        if ($totalReplacements > 0) {
            $io->newLine();
            $io->section('Détails des remplacements par pattern :');
            foreach ($replacementsByPattern as $pattern => $count) {
                if ($count > 0) {
                    $io->writeln(sprintf('  • "<comment>%s</comment>" : <info>%d</info> remplacements', $pattern, $count));
                }
            }
        } else {
            $io->info('Aucune occurrence des numéros de téléphone n\'a été trouvée dans les fichiers de traduction.');
        }
        
        return Command::SUCCESS;
    }
}
