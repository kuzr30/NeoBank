<?php

namespace App\Command;

use App\Entity\User;
use App\Service\AccountCreationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-missing-accounts',
    description: 'Créer les comptes principaux manquants pour les utilisateurs existants',
)]
class CreateMissingAccountsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccountCreationService $accountCreationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Création des comptes principaux manquants');

        // Récupérer tous les utilisateurs
        $users = $this->entityManager->getRepository(User::class)->findAll();
        
        $io->info(sprintf('Nombre d\'utilisateurs trouvés : %d', count($users)));
        
        $accountsCreated = 0;
        $accountsSkipped = 0;

        foreach ($users as $user) {
            try {
                $io->info(sprintf('Traitement utilisateur : %s (%s)', $user->getEmail(), $user->getFirstName() . ' ' . $user->getLastName()));
                
                $account = $this->accountCreationService->createDefaultAccountForUser($user);
                
                if ($account) {
                    $accountsCreated++;
                    $io->success(sprintf('✅ Compte créé pour %s - IBAN: %s', $user->getEmail(), $account->getIban()));
                } else {
                    $accountsSkipped++;
                    $io->note(sprintf('⏭️ Compte déjà existant pour %s', $user->getEmail()));
                }
                
            } catch (\Exception $e) {
                $io->error(sprintf('❌ Erreur pour %s : %s', $user->getEmail(), $e->getMessage()));
            }
        }

        $io->success(sprintf(
            'Traitement terminé : %d comptes créés, %d comptes déjà existants',
            $accountsCreated,
            $accountsSkipped
        ));

        return Command::SUCCESS;
    }
}
