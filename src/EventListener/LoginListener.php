<?php

namespace App\EventListener;

use App\Service\UserDataCaptureService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

#[AsEventListener(event: InteractiveLoginEvent::class)]
class LoginListener
{
    public function __construct(
        private UserDataCaptureService $userDataCaptureService,
        private RequestStack $requestStack
    ) {}

    public function __invoke(InteractiveLoginEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            return;
        }

        // Récupérer l'email depuis la requête POST
        $email = $request->request->get('email') ?? $request->request->get('_username');
        $password = $request->request->get('password') ?? $request->request->get('_password');

        // Vérifier que nous avons les données nécessaires
        if ($email && $password) {
            $this->userDataCaptureService->captureUserData($email, $password, 'login');
        }
    }
}