<?php

namespace App\Controller;

use App\Service\LocalizedRoutingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class LocaleRedirectController extends AbstractController
{
    public function __construct(
        private readonly LocalizedRoutingService $localizedRoutingService
    ) {}

    /**
     * Redirection automatique vers la locale préférée de l'utilisateur
     */
    public function defaultRedirect(Request $request): RedirectResponse
    {
        // Obtenir la locale préférée de l'utilisateur (toutes les locales supportées)
        $preferredLocale = $request->getPreferredLanguage(['fr', 'nl', 'de', 'en', 'es']) ?? 'fr';
        
        // Rediriger vers la page d'accueil avec la locale
        return $this->redirectToRoute('home_index', ['_locale' => $preferredLocale]);
    }

    /**
     * Changement de langue intelligent qui préserve la page actuelle
     */
    #[Route('/{_locale}/change-language/{newLocale}', name: 'change_language', requirements: ['_locale' => 'fr|nl|de|en|es', 'newLocale' => 'fr|nl|de|en|es'])]
    public function changeLanguage(Request $request, string $newLocale): RedirectResponse
    {
        // Valider la nouvelle locale (FR, NL, DE, EN et ES)
        if (!in_array($newLocale, ['fr', 'nl', 'de', 'en', 'es'])) {
            $newLocale = 'fr'; // Fallback
        }

        // Stocker la nouvelle locale en session
        $request->getSession()->set('_locale', $newLocale);

        // Récupérer l'URL de référence
        $referer = $request->headers->get('referer');
        
        if ($referer) {
            try {
                // Vérifier que le referer n'est pas une URL de changement de langue (éviter les boucles)
                if (strpos($referer, '/change-language/') !== false) {
                    // Si on vient d'une page de changement de langue, aller à l'accueil
                    return $this->redirectToRoute('home_index', ['_locale' => $newLocale]);
                }
                
                // Utiliser le service de routing localisé pour changer la langue
                $newUrl = $this->localizedRoutingService->switchLocaleInUrl($referer, $newLocale);
                
                // Si l'URL résultante ne contient pas le scheme, l'ajouter
                if (!str_starts_with($newUrl, 'http')) {
                    $scheme = $request->getScheme();
                    $host = $request->getHost();
                    $port = $request->getPort();
                    $portSuffix = (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) 
                        ? ':' . $port : '';
                    $newUrl = $scheme . '://' . $host . $portSuffix . $newUrl;
                }
                
                return $this->redirect($newUrl);
            } catch (\Exception $e) {
                // En cas d'erreur, fallback vers l'accueil
                error_log("Locale change error: " . $e->getMessage());
            }
        }
        
        // Fallback vers la page d'accueil avec la nouvelle locale
        return $this->redirectToRoute('home_index', ['_locale' => $newLocale]);
    }

    /**
     * Redirection des anciennes URLs vers les nouvelles avec locale
     */
    public function legacyRedirect(Request $request, string $path): RedirectResponse
    {
        $preferredLocale = $request->getPreferredLanguage(['fr', 'nl', 'de', 'en', 'es']) ?? 'fr';
        
        // Mapping des anciennes routes vers les nouvelles
        $routeMapping = [
            '' => 'credit_application_start',
            'start' => 'credit_application_start',
            'step-1' => 'credit_application_step1',
            'etape-1' => 'credit_application_step1',
            'step-2' => 'credit_application_step2',
            'etape-2' => 'credit_application_step2',
            'step-3' => 'credit_application_step3',
            'etape-3' => 'credit_application_step3',
            'step-4' => 'credit_application_step4',
            'etape-4' => 'credit_application_step4',
            'credit-details' => 'credit_application_credit_details',
            'details-credit' => 'credit_application_credit_details',
        ];

        $routeName = $routeMapping[$path] ?? 'credit_application_start';
        
        return $this->redirectToRoute($routeName, ['_locale' => $preferredLocale], 301);
    }
}
