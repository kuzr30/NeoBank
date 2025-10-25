<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileEditFormType;
use App\Form\ChangePasswordFormType;
use App\Service\EmailVerificationService;
use App\Service\SecurityNotificationService;
use App\Service\KycService;
use App\Service\ProfessionalTranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/{_locale}/profile', name: 'profile_', requirements: ['_locale' => 'fr|nl|de|en|es'])]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(
        private KycService $kycService,
        private ProfessionalTranslationService $translationService
    ) {
    }

    #[Route([
        'fr' => '/',
        'nl' => '/',
        'en' => '/',
        'de' => '/',
        'es' => '/'
    ], name: 'index', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $kycStatus = $this->kycService->getUserKycStatus($user);
        
        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'kyc_status' => $kycStatus,
        ]);
    }

    #[Route([
        'fr' => '/modifier',
        'nl' => '/bewerken',
        'en' => '/edit',
        'de' => '/bearbeiten',
        'es' => '/editar'
    ], name: 'edit', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function edit(
        Request $request, 
        EntityManagerInterface $entityManager, 
        EmailVerificationService $emailVerificationService
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        $form = $this->createForm(ProfileEditFormType::class, $user);
        
        // Stocker l'email original pour détecter les changements
        $originalEmail = $user->getEmail();
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer le fichier uploadé avant flush
            $uploadedFile = $form->get('profilePictureFile')->getData();
            
            // Vérifier si l'email a changé
            $newEmail = $user->getEmail();
            $emailChanged = $originalEmail !== $newEmail;
            
            if ($emailChanged) {
                try {
                    // Restaurer l'email original et initier le processus de validation
                    $user->setEmail($originalEmail);
                    $emailVerificationService->initiateEmailChange($user, $newEmail);
                    
                    // Sauvegarder les autres modifications
                    $entityManager->flush();
                    
                    // Message spécifique pour le changement d'email
                    $this->addFlash('info', $this->translationService->tp(
                        'profile_controller.flash.email_change_initiated',
                        ['email' => $newEmail],
                        'profile_controller'
                    ));
                    
                } catch (\InvalidArgumentException $e) {
                    $this->addFlash('danger', $e->getMessage());
                    $user->setEmail($originalEmail); // Restaurer l'email original
                }
            } else {
                // Sauvegarder en base (autres modifications)
                $entityManager->flush();
                
                $this->addFlash('success', $this->translationService->tp(
                    'profile_controller.flash.profile_updated',
                    [],
                    'profile_controller'
                ));
            }
            
            // SOLUTION PRO : Vider la propriété File après flush pour éviter la sérialisation
            $user->setProfilePictureFile(null);
            
            return $this->redirectToRoute('profile_index');
        }
        
        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'profileForm' => $form,
        ]);
    }

    #[Route([
        'fr' => '/securite',
        'nl' => '/beveiliging',
        'en' => '/security',
        'de' => '/sicherheit',
        'es' => '/seguridad'
    ], name: 'security', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function security(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer l'activité de sécurité récente
        $recentSecurityActivity = $this->getRecentSecurityActivity($user);
        
        return $this->render('profile/security.html.twig', [
            'user' => $user,
            'recentSecurityActivity' => $recentSecurityActivity,
        ]);
    }

    #[Route([
        'fr' => '/securite/changer-mot-de-passe',
        'nl' => '/beveiliging/wachtwoord-wijzigen',
        'en' => '/security/change-password',
        'de' => '/sicherheit/passwort-andern',
        'es' => '/seguridad/cambiar-contraseña'
    ], name: 'security_change_password', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function changePassword(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        EntityManagerInterface $entityManager, 
        SecurityNotificationService $securityNotificationService
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        $passwordForm = $this->createForm(ChangePasswordFormType::class);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            // Encoder le nouveau mot de passe
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $passwordForm->get('plainPassword')->getData()
                )
            );
            
            // Mettre à jour la date de modification du mot de passe
            $user->setPasswordChangedAt(new \DateTimeImmutable());
            
            $entityManager->flush();
            
            // Envoyer un email de confirmation via le service
            $securityNotificationService->sendPasswordChangeNotification($user);
            
            $this->addFlash('success', 
                $this->translationService->tp('profile_controller.flash.password_changed', [], 'profile_controller')
            );
            
            return $this->redirectToRoute('profile_security');
        }
        
        return $this->render('profile/change_password.html.twig', [
            'user' => $user,
            'passwordForm' => $passwordForm,
        ]);
    }

    #[Route([
        'fr' => '/preferences',
        'nl' => '/voorkeuren',
        'en' => '/preferences',
        'de' => '/einstellungen',
        'es' => '/preferencias'
    ], name: 'preferences', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function preferences(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        return $this->render('profile/preferences.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Récupère l'activité de sécurité récente pour l'utilisateur
     */
    private function getRecentSecurityActivity(User $user): array
    {
        $activities = [];
        
        // Obtenir le fuseau horaire de l'utilisateur
        $userTimezone = new \DateTimeZone($user->getTimezone() ?: 'Europe/Paris');
        
        // Dernière connexion réussie (une seule)
        $lastLogin = $user->getLastLoginAt() ?: new \DateTimeImmutable('now');
        // Convertir au fuseau horaire de l'utilisateur
        $lastLoginUserTime = $lastLogin->setTimezone($userTimezone);
        
        $activities[] = [
            'type' => 'login_success',
            'description' => $this->translationService->tp('profile_controller.security_activities.login_success', [], 'profile_controller'),
            'created_at' => $lastLoginUserTime,
            'icon' => 'check',
            'status' => 'success'
        ];
        
        // Tentative de connexion échouée (si applicable - ici simulée)
        // Dans un vrai système, cela viendrait d'une table de logs de sécurité
        $hasFailedAttempt = true; // Simuler qu'il y a eu une tentative échouée
        if ($hasFailedAttempt) {
            $failedAttempt = new \DateTimeImmutable('-3 days');
            $failedAttemptUserTime = $failedAttempt->setTimezone($userTimezone);
            
            $activities[] = [
                'type' => 'login_failed',
                'description' => $this->translationService->tp('profile_controller.security_activities.login_failed', [], 'profile_controller'),
                'created_at' => $failedAttemptUserTime,
                'icon' => 'exclamation-triangle',
                'status' => 'warning'
            ];
        }
        
        // Modification du mot de passe (si applicable)
        if ($user->getPasswordChangedAt()) {
            $passwordChanged = $user->getPasswordChangedAt()->setTimezone($userTimezone);
            
            $activities[] = [
                'type' => 'password_changed',
                'description' => $this->translationService->tp('profile_controller.security_activities.password_changed', [], 'profile_controller'),
                'created_at' => $passwordChanged,
                'icon' => 'key',
                'status' => 'success'
            ];
        }
        
        return $activities;
    }
}
