<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Vérifie si l'utilisateur a activé son compte lors de la connexion
 */
class LoginSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RouterInterface $router,
        private TokenStorageInterface $tokenStorage,
        private RequestStack $requestStack
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        
        if (!$user instanceof User) {
            return;
        }

        // Si l'utilisateur n'est pas vérifié, on le déconnecte et redirige
        if (!$user->isVerified()) {
            // Déconnecter l'utilisateur
            $this->tokenStorage->setToken(null);
            
            // Invalider la session
            $request = $this->requestStack->getCurrentRequest();
            if ($request && $request->hasSession()) {
                $request->getSession()->invalidate();
            }
            
            // Rediriger vers la page d'activation
            $url = $this->router->generate('app_account_activation_notice');
            $response = new RedirectResponse($url);
            $event->setResponse($response);
        }
    }
}
