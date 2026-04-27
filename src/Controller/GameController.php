<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Form\GameType;
use App\Repository\DeckRepository;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/games')]
class GameController extends AbstractController
{
    #[Route('', name: 'game_index', methods: ['GET'])]
    public function index(GameRepository $repo): Response
    {
        return $this->render('game/index.html.twig', ['games' => $repo->findRecent(50)]);
    }

    #[Route('/new', name: 'game_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        PlayerRepository $playerRepo,
        DeckRepository $deckRepo,
    ): Response {
        $game = new Game();
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $players = $request->request->all('participants') ?: [];
            foreach ($players as $i => $data) {
                if (empty($data['player_id'])) continue;
                $player = $playerRepo->find($data['player_id']);
                if (!$player) continue;

                $gp = new GamePlayer();
                $gp->setPlayer($player);
                $gp->setPlacement($data['placement'] ? (int) $data['placement'] : null);
                $gp->setWinner(isset($data['winner']) && $data['winner'] === '1');

                if (!empty($data['deck_id'])) {
                    $deck = $deckRepo->find($data['deck_id']);
                    if ($deck) $gp->setDeck($deck);
                }

                $game->addParticipant($gp);
            }

            $em->persist($game);
            $em->flush();
            $this->addFlash('success', 'Game recorded!');
            return $this->redirectToRoute('game_show', ['id' => $game->getId()]);
        }

        return $this->render('game/form.html.twig', [
            'form' => $form,
            'title' => 'Record Game',
            'players' => $playerRepo->findAll(),
            'decks' => $deckRepo->findAll(),
            'game' => null,
        ]);
    }

    #[Route('/{id}', name: 'game_show', methods: ['GET'])]
    public function show(Game $game): Response
    {
        return $this->render('game/show.html.twig', ['game' => $game]);
    }

    #[Route('/{id}/edit', name: 'game_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Game $game,
        EntityManagerInterface $em,
        PlayerRepository $playerRepo,
        DeckRepository $deckRepo,
    ): Response {
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($game->getParticipants() as $p) {
                $em->remove($p);
            }
            $game->getParticipants()->clear();

            $players = $request->request->all('participants') ?: [];
            foreach ($players as $data) {
                if (empty($data['player_id'])) continue;
                $player = $playerRepo->find($data['player_id']);
                if (!$player) continue;

                $gp = new GamePlayer();
                $gp->setPlayer($player);
                $gp->setPlacement($data['placement'] ? (int) $data['placement'] : null);
                $gp->setWinner(isset($data['winner']) && $data['winner'] === '1');

                if (!empty($data['deck_id'])) {
                    $deck = $deckRepo->find($data['deck_id']);
                    if ($deck) $gp->setDeck($deck);
                }

                $game->addParticipant($gp);
            }

            $em->flush();
            $this->addFlash('success', 'Game updated!');
            return $this->redirectToRoute('game_show', ['id' => $game->getId()]);
        }

        return $this->render('game/form.html.twig', [
            'form' => $form,
            'title' => 'Edit Game',
            'players' => $playerRepo->findAll(),
            'decks' => $deckRepo->findAll(),
            'game' => $game,
        ]);
    }

    #[Route('/{id}/delete', name: 'game_delete', methods: ['POST'])]
    public function delete(Request $request, Game $game, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $game->getId(), $request->request->get('_token'))) {
            $em->remove($game);
            $em->flush();
            $this->addFlash('success', 'Game deleted.');
        }
        return $this->redirectToRoute('game_index');
    }
}
