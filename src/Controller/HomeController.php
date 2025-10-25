<?php

namespace App\Controller;

use App\Service\ProfessionalTranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private ProfessionalTranslationService $translationService
    ) {}

    #[Route('/{_locale}', name: 'home_index', requirements: ['_locale' => 'fr|nl|de|en|es'])]
    public function index(Request $request): Response
    {
        $locale = $request->getLocale();
        $this->translationService->setLocale($locale);

        return $this->render('home/index.html.twig');
    }

    #[Route('/', name: 'home_default')]
    public function homeDefault(Request $request): Response
    {
        $preferredLocale = $request->getPreferredLanguage(['fr', 'nl', 'de', 'en', 'es']) ?? 'fr';
        return $this->redirectToRoute('home_index', ['_locale' => $preferredLocale]);
    }

    #[Route('/lang/{locale}', name: 'switch_language')]
    public function switchLanguage(
        string $locale, 
        Request $request, 
        SessionInterface $session
    ): Response {
        if (in_array($locale, $this->translationService->getSupportedLocales())) {
            $session->set('_locale', $locale);
            $request->setLocale($locale);
        }

        // Rediriger vers la page de changement de langue plus sophistiquée
        return $this->redirectToRoute('change_language', [
            '_locale' => $request->getLocale(),
            'newLocale' => $locale
        ]);
    }
}
