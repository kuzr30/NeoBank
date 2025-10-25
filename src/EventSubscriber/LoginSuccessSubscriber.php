<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class LoginSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
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

        $this->updateLastLoginTime($user);
        $targetRoute = $this->determineTargetRoute($user);
        $targetUrl = $this->urlGenerator->generate($targetRoute, $targetRoute === 'home_index' ? ['_locale' => 'fr'] : []);
        
        $response = new RedirectResponse($targetUrl);
        $event->setResponse($response);

        $this->logger->info('Utilisateur connecté avec succès', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'target_route' => $targetRoute,
            'target_url' => $targetUrl
        ]);
    }

    private function updateLastLoginTime(User $user): void
    {
        try {
            $user->setLastLoginAt(new \DateTimeImmutable());
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour de lastLoginAt', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    private function determineTargetRoute(User $user): string
    {
        $roles = $user->getRoles();

        if (in_array('ROLE_SUPER_ADMIN', $roles)) {
            return 'app_admin_dashboard';
        }

        if (in_array('ROLE_ADMIN', $roles)) {
            return 'app_admin_dashboard';
        }

        if (in_array('ROLE_CLIENT', $roles)) {
            return 'banking_dashboard';
        }

        return 'home_index';
    }
}
