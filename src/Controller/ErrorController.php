<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ErrorController extends AbstractController
{
    public function show(FlattenException $exception, Request $request): Response
    {
        $statusCode = $exception->getStatusCode();
        
        // Essayer de trouver un template spécifique pour ce code d'erreur
        $template = sprintf('bundles/TwigBundle/Exception/error%d.html.twig', $statusCode);
        
        // Si le template spécifique n'existe pas, utiliser le template générique
        try {
            $this->container->get('twig')->load($template);
        } catch (\Twig\Error\LoaderError $e) {
            $template = 'bundles/TwigBundle/Exception/error.html.twig';
        }
        
        return $this->render($template, [
            'status_code' => $statusCode,
            'status_text' => Response::$statusTexts[$statusCode] ?? 'Unknown error',
            'exception' => $exception,
        ]);
    }
}
