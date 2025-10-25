<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthenticationController extends AbstractController
{
    #[Route([
        'fr' => '/{_locale}/connexion',
        'nl' => '/{_locale}/inloggen',
        'en' => '/{_locale}/login',
        'de' => '/{_locale}/anmelden',
        'es' => '/{_locale}/iniciar-sesion'
    ], name: 'app_login', requirements: ['_locale' => 'fr|nl|de|en|es'], methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // Rediriger si déjà connecté
        if ($this->getUser()) {
            $locale = $request->get('_locale', 'fr');
            return $this->redirectToRoute('home_index', ['_locale' => $locale]);
        }

        // Récupérer les erreurs d'authentification et le dernier username
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route([
        'fr' => '/{_locale}/deconnexion',
        'nl' => '/{_locale}/uitloggen',
        'en' => '/{_locale}/logout',
        'de' => '/{_locale}/abmelden',
        'es' => '/{_locale}/cerrar-sesion'
    ], name: 'app_logout', requirements: ['_locale' => 'fr|nl|de|en|es'], methods: ['GET'])]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/admin/logout', name: 'admin_logout', methods: ['GET'])]
    public function adminLogout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
