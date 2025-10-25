<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin-user',
    description: 'Créer un utilisateur administrateur',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'administrateur')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe de l\'administrateur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        
        if ($existingUser) {
            // Mettre à jour les rôles de l'utilisateur existant
            $existingUser->setRoles(['ROLE_ADMIN', 'ROLE_CLIENT']);
            $hashedPassword = $this->passwordHasher->hashPassword($existingUser, $password);
            $existingUser->setPassword($hashedPassword);
            
            $this->entityManager->flush();
            
            $io->success("L'utilisateur $email a été mis à jour avec les droits administrateur.");
            return Command::SUCCESS;
        }

        // Créer un nouvel utilisateur admin
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Admin');
        $user->setLastName('System');
        $user->setRoles(['ROLE_ADMIN', 'ROLE_CLIENT']);
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        $user->setIsVerified(true);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success("Utilisateur administrateur créé avec succès !");
        $io->table(
            ['Email', 'Rôles'],
            [[$email, 'ROLE_ADMIN, ROLE_CLIENT']]
        );

        return Command::SUCCESS;
    }
}
