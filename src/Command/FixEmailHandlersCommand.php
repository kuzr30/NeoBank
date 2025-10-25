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
    name: 'app:fix-email-handlers',
    description: 'Ajoute le support des placeholders d\'entreprise dans tous les handlers d\'emails',
)]
class FixEmailHandlersCommand extends Command
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
        
        $io->title('Ajout du support des placeholders d\'entreprise dans les handlers d\'emails');
        
        $handlersDir = $this->projectDir . '/src/MessageHandler';
        
        if (!is_dir($handlersDir)) {
            $io->error('Le répertoire MessageHandler n\'existe pas.');
            return Command::FAILURE;
        }
        
        // Trouve tous les fichiers *EmailMessageHandler.php
        $finder = new Finder();
        $finder->files()->in($handlersDir)->name('*EmailMessageHandler.php');
        
        $filesProcessed = 0;
        $filesModified = 0;
        
        foreach ($finder as $file) {
            $filesProcessed++;
            $filePath = $file->getRealPath();
            $content = file_get_contents($filePath);
            $originalContent = $content;
            $modified = false;
            
            // Ignorer AccountActivationEmailMessageHandler car déjà modifié
            if (str_contains($content, 'class AccountActivationEmailMessageHandler')) {
                $io->writeln(sprintf('<comment>⊘</comment> %s (déjà modifié)', $file->getFilename()));
                continue;
            }
            
            // Ignorer les classes readonly
            if (str_contains($content, 'readonly class')) {
                $io->writeln(sprintf('<comment>⊘</comment> %s (classe readonly, ignorée)', $file->getFilename()));
                continue;
            }
            
            // 1. Ajouter l'import du trait si pas déjà présent
            if (!str_contains($content, 'use App\Trait\CompanyPlaceholderReplacerTrait;')) {
                $content = str_replace(
                    'use App\Service\EmailService;',
                    "use App\Service\EmailService;\nuse App\Trait\CompanyPlaceholderReplacerTrait;",
                    $content
                );
                $modified = true;
            }
            
            // 2. Ajouter l'import de CompanySettingsService si pas déjà présent
            if (!str_contains($content, 'use App\Service\CompanySettingsService;')) {
                $content = str_replace(
                    'use App\Service\EmailService;',
                    "use App\Service\EmailService;\nuse App\Service\CompanySettingsService;",
                    $content
                );
                $modified = true;
            }
            
            // 3. Ajouter le trait dans la classe si pas déjà présent
            if (!str_contains($content, 'use CompanyPlaceholderReplacerTrait;')) {
                // Trouver la déclaration de la classe
                if (preg_match('/(class\s+\w+\s*\n\s*{)/', $content, $matches)) {
                    $content = str_replace(
                        $matches[1],
                        $matches[1] . "\n    use CompanyPlaceholderReplacerTrait;\n",
                        $content
                    );
                    $modified = true;
                }
            }
            
            // 4. Ajouter CompanySettingsService dans le constructeur si pas déjà présent
            if (!str_contains($content, 'CompanySettingsService $companySettingsService')) {
                // Trouver le constructeur et ajouter le paramètre
                if (preg_match('/public function __construct\((.*?)\)/s', $content, $matches)) {
                    $constructorParams = $matches[1];
                    
                    // Ajouter le paramètre avant les paramètres avec #[Autowire]
                    if (str_contains($constructorParams, '#[Autowire')) {
                        $newParams = preg_replace(
                            '/(.*?)(#\[Autowire.*)/s',
                            '$1private CompanySettingsService $companySettingsService,' . "\n        " . '$2',
                            $constructorParams
                        );
                    } else {
                        // Sinon, l'ajouter à la fin
                        $newParams = rtrim($constructorParams, ',') . ",\n        private CompanySettingsService \$companySettingsService";
                    }
                    
                    $content = str_replace(
                        'public function __construct(' . $constructorParams . ')',
                        'public function __construct(' . $newParams . ')',
                        $content
                    );
                    $modified = true;
                }
            }
            
            // 5. Ajouter le remplacement des placeholders dans le sujet
            if (!str_contains($content, '$this->replaceCompanyPlaceholders($subject)')) {
                // Chercher où le sujet est traduit
                if (preg_match('/(\\$subject = \\$this->translator->trans\\(.*?\\);)/s', $content, $matches)) {
                    $content = str_replace(
                        $matches[1],
                        $matches[1] . "\n                \$subject = \$this->replaceCompanyPlaceholders(\$subject);",
                        $content
                    );
                    $modified = true;
                }
            }
            
            if ($modified && $content !== $originalContent) {
                file_put_contents($filePath, $content);
                $filesModified++;
                $io->writeln(sprintf('<info>✓</info> %s', $file->getFilename()));
            } elseif (!$modified) {
                $io->writeln(sprintf('<comment>⊘</comment> %s (aucune modification nécessaire)', $file->getFilename()));
            }
        }
        
        $io->newLine();
        $io->success('Traitement terminé !');
        $io->writeln([
            sprintf('Fichiers analysés : <info>%d</info>', $filesProcessed),
            sprintf('Fichiers modifiés : <info>%d</info>', $filesModified),
        ]);
        
        if ($filesModified > 0) {
            $io->note('N\'oubliez pas de vider le cache avec : php bin/console cache:clear');
        }
        
        return Command::SUCCESS;
    }
}
