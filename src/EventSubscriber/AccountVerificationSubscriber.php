<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Vérifie que l'utilisateur connecté a bien activé son compte
 */
class AccountVerificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private RouterInterface $router,
        private EntityManagerInterface $entityManager
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Routes autorisées pour les utilisateurs non vérifiés
        $allowedRoutes = [
            'app_login',
            'app_logout', 
            'app_register',
            'app_account_activation',
            'app_resend_activation',
            'app_forgot_password_request',
            'app_reset_password',
            'app_check_email',
            'home_index',
            'main_index',
            'change_language'
        ];

        // Routes publiques (commence par _)
        if (str_starts_with($route, '_')) {
            return;
        }

        // Routes autorisées
        if (in_array($route, $allowedRoutes)) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token instanceof TokenInterface) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return;
        }

        // Mettre à jour lastLoginAt si l'utilisateur est vérifié
        if ($user->isVerified()) {
            $user->setLastLoginAt(new \DateTimeImmutable());
            $this->entityManager->flush();
            return;
        }

        // Rediriger vers la page d'activation si l'utilisateur n'est pas vérifié
        $activationUrl = $this->router->generate('app_account_activation_notice');
        $event->setResponse(new RedirectResponse($activationUrl));
    }
}
