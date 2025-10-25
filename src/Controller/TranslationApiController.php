<?php

namespace App\Controller;

use App\Service\ProfessionalTranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/translations', name: 'api_translations_')]
class TranslationApiController extends AbstractController
{
    public function __construct(
        private ProfessionalTranslationService $translationService
    ) {}

    /**
     * API pour récupérer les traductions d'une section spécifique
     * Utile pour les applications SPA ou AJAX
     */
    #[Route('/section/{section}', name: 'get_section', methods: ['GET'])]
    public function getSection(string $section, Request $request): JsonResponse
    {
        $page = $request->query->get('page');
        $locale = $request->query->get('locale', $request->getLocale());
        
        // Changer temporairement la locale si nécessaire
        $currentLocale = $this->translationService->getLocale();
        if ($locale !== $currentLocale) {
            $this->translationService->setLocale($locale);
        }
        
        $translations = $this->translationService->getSection($section, $page);
        
        // Restaurer la locale originale
        if ($locale !== $currentLocale) {
            $this->translationService->setLocale($currentLocale);
        }
        
        return $this->json([
            'section' => $section,
            'page' => $page,
            'locale' => $locale,
            'translations' => $translations
        ]);
    }

    /**
     * API pour récupérer une traduction spécifique
     */
    #[Route('/key/{key}', name: 'get_key', methods: ['GET'])]
    public function getKey(string $key, Request $request): JsonResponse
    {
        $page = $request->query->get('page');
        $locale = $request->query->get('locale', $request->getLocale());
        $parameters = $request->query->all('params') ?? [];
        
        // Changer temporairement la locale si nécessaire
        $currentLocale = $this->translationService->getLocale();
        if ($locale !== $currentLocale) {
            $this->translationService->setLocale($locale);
        }
        
        $translation = $this->translationService->trans($key, $parameters, $page);
        
        // Restaurer la locale originale
        if ($locale !== $currentLocale) {
            $this->translationService->setLocale($currentLocale);
        }
        
        return $this->json([
            'key' => $key,
            'page' => $page,
            'locale' => $locale,
            'parameters' => $parameters,
            'translation' => $translation
        ]);
    }

    /**
     * API pour récupérer toutes les langues disponibles
     */
    #[Route('/locales', name: 'get_locales', methods: ['GET'])]
    public function getLocales(): JsonResponse
    {
        return $this->json([
            'available_locales' => $this->translationService->getAvailableLocales(),
            'default_locale' => $this->translationService->getDefaultLocale(),
            'current_locale' => $this->translationService->getLocale()
        ]);
    }
}
