<?php

namespace App\Controller;

use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(GameRepository $gameRepo, PlayerRepository $playerRepo): Response
    {
        return $this->render('home/index.html.twig', [
            'recentGames' => $gameRepo->findRecent(5),
            'playerCount' => count($playerRepo->findAll()),
            'gameCount' => count($gameRepo->findAll()),
        ]);
    }
}
