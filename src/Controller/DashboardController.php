<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        if (!$this->isGranted(User::ROLE_USER_ADMIN)) {
            return $this->redirect('/');
        }

        return $this->render('dashboard/index.html.twig');
    }
}
