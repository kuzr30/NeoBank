<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-super',
    description: '🔐 Créer un super administrateur SEDEF BANK avec tous les privilèges'
)]
class CreateSuperAdminCommand extends Command
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Cette commande permet de créer un super administrateur avec tous les privilèges du système SEDEF BANK.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('👑 Création d\'un SUPER ADMINISTRATEUR SEDEF BANK');
        $io->warning([
            '⚠️  ATTENTION: Vous êtes sur le point de créer un SUPER ADMINISTRATEUR',
            '🔥 Ce compte aura un accès TOTAL à toutes les fonctionnalités',
            '💀 Ceci inclut la gestion des autres admins et la suppression de données',
            '🛡️  Utilisez ce privilège avec une extrême prudence!'
        ]);
        $io->newLine();

        if (!$io->confirm('Êtes-vous ABSOLUMENT SÛR de vouloir créer un super administrateur ?', false)) {
            $io->error('❌ Création annulée pour votre sécurité.');
            return Command::FAILURE;
        }

        $io->newLine();
        $io->section('📋 Informations du Super Administrateur');

        // Demander l'email de manière interactive
        $email = $io->ask(
            'Quel est l\'email du super administrateur ?',
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
            if (in_array('ROLE_SUPER_ADMIN', $existingUser->getRoles())) {
                $io->warning(sprintf('⚠️  Un super administrateur avec l\'email "%s" existe déjà.', $email));
                
                if ($io->confirm('Voulez-vous réinitialiser son mot de passe ?', false)) {
                    return $this->resetSuperAdminPassword($io, $existingUser);
                }
                
                return Command::SUCCESS;
            } else {
                $io->error(sprintf('❌ Un utilisateur avec l\'email "%s" existe déjà mais n\'est pas super admin.', $email));
                return Command::FAILURE;
            }
        }

        // Demander le mot de passe de manière interactive (masqué)
        $password = $io->askHidden(
            '🔒 Quel est le mot de passe ? (minimum 12 caractères pour un super admin)',
            function ($value) {
                if (empty($value)) {
                    throw new \RuntimeException('Le mot de passe ne peut pas être vide.');
                }
                if (strlen($value) < 12) {
                    throw new \RuntimeException('Le mot de passe doit contenir AU MOINS 12 caractères pour un super administrateur.');
                }
                if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $value)) {
                    throw new \RuntimeException('Le mot de passe doit contenir: majuscule, minuscule, chiffre et caractère spécial.');
                }
                return $value;
            }
        );

        // Confirmation du mot de passe
        $confirmPassword = $io->askHidden(
            '🔒 Confirmez le mot de passe',
            function ($value) use ($password) {
                if ($value !== $password) {
                    throw new \RuntimeException('Les mots de passe ne correspondent pas.');
                }
                return $value;
            }
        );

        // Demander le prénom
        $firstName = $io->ask(
            'Quel est le prénom du super administrateur ?',
            'Super',
            function ($value) {
                if (empty(trim($value))) {
                    throw new \RuntimeException('Le prénom ne peut pas être vide.');
                }
                return trim($value);
            }
        );

        // Demander le nom
        $lastName = $io->ask(
            'Quel est le nom de famille du super administrateur ?',
            'Admin',
            function ($value) {
                if (empty(trim($value))) {
                    throw new \RuntimeException('Le nom ne peut pas être vide.');
                }
                return trim($value);
            }
        );

        $io->newLine();
        
        // Afficher un récapitulatif
        $io->section('📋 Récapitulatif du Super Administrateur');
        $io->definitionList(
            ['Email' => $email],
            ['Prénom' => $firstName],
            ['Nom' => $lastName],
            ['Rôles' => '👑 ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_USER'],
            ['Privilèges' => '🔥 ACCÈS TOTAL AU SYSTÈME'],
            ['Compte vérifié' => 'Oui']
        );

        $io->warning([
            '⚠️  DERNIÈRE CHANCE D\'ANNULER',
            '🚨 Ce compte aura le pouvoir de:',
            '• Supprimer tous les utilisateurs et admins',
            '• Modifier toutes les données bancaires',
            '• Accéder à tous les comptes clients',
            '• Contrôler tous les crédits et transactions',
            '• Gérer l\'ensemble du système'
        ]);

        // Demander confirmation finale
        if (!$io->confirm('Êtes-vous ABSOLUMENT CERTAIN de créer ce super administrateur ?', false)) {
            $io->warning('❌ Création annulée - Sage décision.');
            return Command::SUCCESS;
        }

        // Créer le nouveau super administrateur
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_USER']);
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
            $io->text('💾 Création du super administrateur en cours...');
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $io->success([
                '👑 SUPER ADMINISTRATEUR CRÉÉ AVEC SUCCÈS !',
                '',
                sprintf('👤 %s %s (%s)', $firstName, $lastName, $email),
                '🔑 Rôles : ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_USER',
                '🔥 Privilèges : ACCÈS TOTAL AU SYSTÈME',
                '✓ Compte vérifié et opérationnel',
                '',
                '🌐 Connexion disponible sur /fr/login ou /your-are-in-my/Zve007',
                '',
                '⚠️  RAPPEL: Gardez ces identifiants EN SÉCURITÉ ABSOLUE !'
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('❌ Erreur lors de la création du super administrateur : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function resetSuperAdminPassword(SymfonyStyle $io, User $user): int
    {
        $io->section('🔄 Réinitialisation du mot de passe');
        
        // Demander le nouveau mot de passe
        $password = $io->askHidden(
            '🔒 Nouveau mot de passe (minimum 12 caractères)',
            function ($value) {
                if (empty($value)) {
                    throw new \RuntimeException('Le mot de passe ne peut pas être vide.');
                }
                if (strlen($value) < 12) {
                    throw new \RuntimeException('Le mot de passe doit contenir AU MOINS 12 caractères.');
                }
                if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $value)) {
                    throw new \RuntimeException('Le mot de passe doit contenir: majuscule, minuscule, chiffre et caractère spécial.');
                }
                return $value;
            }
        );

        // Confirmation
        $confirmPassword = $io->askHidden(
            '🔒 Confirmez le nouveau mot de passe',
            function ($value) use ($password) {
                if ($value !== $password) {
                    throw new \RuntimeException('Les mots de passe ne correspondent pas.');
                }
                return $value;
            }
        );

        try {
            // Hasher et sauvegarder le nouveau mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            $this->entityManager->flush();

            $io->success([
                '✅ Mot de passe du super administrateur réinitialisé !',
                '',
                sprintf('👤 %s %s (%s)', $user->getFirstName(), $user->getLastName(), $user->getEmail()),
                '🔑 Nouveau mot de passe appliqué',
                '',
                '⚠️  Gardez ce nouveau mot de passe EN SÉCURITÉ !'
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('❌ Erreur lors de la réinitialisation : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
