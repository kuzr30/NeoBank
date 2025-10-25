<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Contrôleur pour gérer les URLs d'authentification sans locale
 * Redirige vers les contrôleurs principaux avec la bonne locale
 */
class LocalizedAuthController extends AbstractController
{
    // Routes de fallback sans locale (pour la compatibilité)
    #[Route('/login', name: 'app_login_legacy')]
    public function loginLegacy(Request $request): Response
    {
        $preferredLocale = $request->getPreferredLanguage(['fr', 'nl', 'de', 'en', 'es']) ?? 'fr';
        return $this->redirectToRoute('app_login', ['_locale' => $preferredLocale], 301);
    }

    #[Route('/register', name: 'app_register_legacy')]
    public function registerLegacy(Request $request): Response
    {
        $preferredLocale = $request->getPreferredLanguage(['fr', 'nl', 'de', 'en', 'es']) ?? 'fr';
        return $this->redirectToRoute('app_register', ['_locale' => $preferredLocale], 301);
    }
}
