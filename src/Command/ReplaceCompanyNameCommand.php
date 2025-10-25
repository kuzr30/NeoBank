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
    name: 'app:replace-company-name',
    description: 'Remplace "SEDEF BANK" par "%company_name%" dans tous les fichiers de traduction',
)]
class ReplaceCompanyNameCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Remplacement de "SEDEF BANK" dans les fichiers de traduction');
        
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
        
        foreach ($finder as $file) {
            $filesProcessed++;
            $filePath = $file->getRealPath();
            $content = file_get_contents($filePath);
            
            // Compte les occurrences
            $count = substr_count($content, 'SEDEF BANK');
            
            if ($count > 0) {
                // Remplace "SEDEF BANK" par "%company_name%"
                $newContent = str_replace('SEDEF BANK', '%company_name%', $content);
                
                // Sauvegarde le fichier
                file_put_contents($filePath, $newContent);
                
                $filesModified++;
                $totalReplacements += $count;
                
                $io->writeln(sprintf(
                    '<info>✓</info> %s (<comment>%d remplacement(s)</comment>)',
                    $file->getRelativePathname(),
                    $count
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
        
        if ($totalReplacements === 0) {
            $io->info('Aucune occurrence de "SEDEF BANK" n\'a été trouvée dans les fichiers de traduction.');
        }
        
        return Command::SUCCESS;
    }
}
