<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    /**
     * Cette route est maintenant gérée par HomeController::homeDefault()
     * On la supprime pour éviter les conflits
     */
}
