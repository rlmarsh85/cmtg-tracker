<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stats')]
class StatsController extends AbstractController
{
    #[Route('', name: 'stats_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('stats/index.html.twig');
    }
}
