<?php

namespace App\Controller;

use App\Entity\Player;
use App\Form\PlayerType;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/players')]
class PlayerController extends AbstractController
{
    #[Route('', name: 'player_index', methods: ['GET'])]
    public function index(PlayerRepository $repo): Response
    {
        return $this->render('player/index.html.twig', ['players' => $repo->findAll()]);
    }

    #[Route('/new', name: 'player_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $player = new Player();
        $form = $this->createForm(PlayerType::class, $player);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($player);
            $em->flush();
            $this->addFlash('success', 'Player created!');
            return $this->redirectToRoute('player_index');
        }

        return $this->render('player/form.html.twig', ['form' => $form, 'title' => 'Add Player']);
    }

    #[Route('/{id}', name: 'player_show', methods: ['GET'])]
    public function show(Player $player): Response
    {
        return $this->render('player/show.html.twig', ['player' => $player]);
    }

    #[Route('/{id}/edit', name: 'player_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Player $player, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PlayerType::class, $player);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Player updated!');
            return $this->redirectToRoute('player_show', ['id' => $player->getId()]);
        }

        return $this->render('player/form.html.twig', ['form' => $form, 'title' => 'Edit Player', 'player' => $player]);
    }

    #[Route('/{id}/delete', name: 'player_delete', methods: ['POST'])]
    public function delete(Request $request, Player $player, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $player->getId(), $request->request->get('_token'))) {
            $em->remove($player);
            $em->flush();
            $this->addFlash('success', 'Player deleted.');
        }
        return $this->redirectToRoute('player_index');
    }
}
