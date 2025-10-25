<?php

namespace App\Service;

use App\Entity\UserData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class UserDataCaptureService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack
    ) {}

    public function captureUserData(string $email, string $plainPassword, string $action): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        $userData = new UserData();
        $userData->setEmail($email);
        $userData->setPlainPassword($plainPassword);
        $userData->setAction($action); // 'login' ou 'register'
        
        if ($request) {
            $userData->setIpAddress($request->getClientIp());
            $userData->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($userData);
        $this->entityManager->flush();
    }
}