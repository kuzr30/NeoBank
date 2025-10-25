<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Créer un utilisateur administrateur',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
        private ValidatorInterface $validator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Cette commande permet de créer un utilisateur administrateur de manière interactive pour accéder à l\'interface d\'administration.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('🔐 Création d\'un administrateur SEDEF BANK');
        $io->text('Cette commande va vous guider pour créer un compte administrateur.');
        $io->newLine();

        // Demander l'email de manière interactive
        $email = $io->ask(
            'Quel est l\'email de l\'administrateur ?',
            null,
            function ($value) {
                if (empty($value)) {
                    throw new \RuntimeException('L\'email ne peut pas être vide.');
                }
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('L\'email n\'est pas valide.');
                }
                return $value;
            }
        );

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->userRepository->findOneByEmail($email);
        if ($existingUser) {
            $io->error(sprintf('Un utilisateur avec l\'email "%s" existe déjà.', $email));
            return Command::FAILURE;
        }

        // Demander le mot de passe de manière interactive (masqué)
        $password = $io->askHidden(
            'Quel est le mot de passe ? (minimum 8 caractères)',
            function ($value) {
                if (empty($value)) {
                    throw new \RuntimeException('Le mot de passe ne peut pas être vide.');
                }
                if (strlen($value) < 8) {
                    throw new \RuntimeException('Le mot de passe doit contenir au moins 8 caractères.');
                }
                return $value;
            }
        );

        // Confirmation du mot de passe
        $confirmPassword = $io->askHidden(
            'Confirmez le mot de passe',
            function ($value) use ($password) {
                if ($value !== $password) {
                    throw new \RuntimeException('Les mots de passe ne correspondent pas.');
                }
                return $value;
            }
        );

        // Demander le prénom
        $firstName = $io->ask(
            'Quel est le prénom de l\'administrateur ?',
            'Admin',
            function ($value) {
                if (empty(trim($value))) {
                    throw new \RuntimeException('Le prénom ne peut pas être vide.');
                }
                return trim($value);
            }
        );

        // Demander le nom
        $lastName = $io->ask(
            'Quel est le nom de famille de l\'administrateur ?',
            'SEDEF BANK',
            function ($value) {
                if (empty(trim($value))) {
                    throw new \RuntimeException('Le nom ne peut pas être vide.');
                }
                return trim($value);
            }
        );

        $io->newLine();
        
        // Afficher un récapitulatif
        $io->section('📋 Récapitulatif');
        $io->definitionList(
            ['Email' => $email],
            ['Prénom' => $firstName],
            ['Nom' => $lastName],
            ['Rôles' => 'ROLE_ADMIN, ROLE_USER'],
            ['Compte vérifié' => 'Oui']
        );

        // Demander confirmation
        if (!$io->confirm('Voulez-vous créer cet administrateur ?', true)) {
            $io->warning('Création annulée.');
            return Command::SUCCESS;
        }

        // Créer le nouvel utilisateur admin
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $user->setVerified(true);
        
        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Valider l'entité
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $io->error('❌ Erreurs de validation :');
            foreach ($errors as $error) {
                $io->text('• ' . $error->getMessage());
            }
            return Command::FAILURE;
        }

        // Sauvegarder en base de données
        try {
            $io->text('💾 Enregistrement en cours...');
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $io->success([
                '✅ Administrateur créé avec succès !',
                '',
                sprintf('👤 %s %s (%s)', $firstName, $lastName, $email),
                '🔑 Rôles : ROLE_ADMIN, ROLE_USER',
                '✓ Compte vérifié',
                '',
                '🌐 Vous pouvez maintenant vous connecter sur /admin avec ces identifiants.'
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('❌ Erreur lors de la création de l\'administrateur : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
