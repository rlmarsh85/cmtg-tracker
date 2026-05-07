<?php

namespace App\Controller;

use App\Entity\Deck;
use App\Form\DeckType;
use App\Repository\ColorIdentityRepository;
use App\Repository\DeckRepository;
use App\Service\CommanderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/decks')]
class DeckController extends AbstractController
{
    #[Route('', name: 'deck_index', methods: ['GET'])]
    public function index(DeckRepository $repo): Response
    {
        return $this->render('deck/index.html.twig', ['decks' => $repo->findAll()]);
    }

    #[Route('/new', name: 'deck_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ColorIdentityRepository $ciRepo, CommanderService $commanderService): Response
    {
        $deck = new Deck();
        $form = $this->createForm(DeckType::class, $deck);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $deck->setColorIdentity($ciRepo->findByColorNames($form->get('colors')->getData() ?? []));
            $this->resolveCommanders($form, $deck, $commanderService);
            $em->persist($deck);
            $em->flush();
            $this->addFlash('success', 'Deck created!');
            return $this->redirectToRoute('deck_index');
        }

        return $this->render('deck/form.html.twig', ['form' => $form, 'title' => 'Add Deck']);
    }

    #[Route('/{id}', name: 'deck_show', methods: ['GET'])]
    public function show(Deck $deck): Response
    {
        return $this->render('deck/show.html.twig', ['deck' => $deck]);
    }

    #[Route('/{id}/edit', name: 'deck_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Deck $deck, EntityManagerInterface $em, ColorIdentityRepository $ciRepo, CommanderService $commanderService): Response
    {
        $form = $this->createForm(DeckType::class, $deck);

        if ($deck->getColorIdentity()) {
            $existing = $deck->getColorIdentity()->getColors()->map(fn($c) => $c->getName())->toArray();
            $form->get('colors')->setData($existing);
        }
        if ($deck->getCommander()) {
            $form->get('commander')->setData($deck->getCommander()->getName());
        }
        if ($deck->getPartner()) {
            $form->get('partner')->setData($deck->getPartner()->getName());
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $deck->setColorIdentity($ciRepo->findByColorNames($form->get('colors')->getData() ?? []));
            $this->resolveCommanders($form, $deck, $commanderService);
            $em->flush();
            $this->addFlash('success', 'Deck updated!');
            return $this->redirectToRoute('deck_show', ['id' => $deck->getId()]);
        }

        return $this->render('deck/form.html.twig', ['form' => $form, 'title' => 'Edit Deck', 'deck' => $deck]);
    }

    #[Route('/{id}/delete', name: 'deck_delete', methods: ['POST'])]
    public function delete(Request $request, Deck $deck, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $deck->getId(), $request->request->get('_token'))) {
            $em->remove($deck);
            $em->flush();
            $this->addFlash('success', 'Deck deleted.');
        }
        return $this->redirectToRoute('deck_index');
    }

    private function resolveCommanders($form, Deck $deck, CommanderService $commanderService): void
    {
        if ($deck->getFormat() !== 'Commander') {
            $deck->setCommander(null);
            $deck->setPartner(null);
            return;
        }

        $commanderName = trim($form->get('commander')->getData() ?? '');
        if ($commanderName) {
            $colorLetters = $this->parseLetters($form->get('commander_colors')->getData());
            $deck->setCommander($commanderService->findOrCreate(
                $commanderName,
                $colorLetters,
                $form->get('commander_partner_type')->getData() ?: null,
                $form->get('commander_partner_with')->getData() ?: null,
                $form->get('commander_image_uri')->getData() ?: null,
            ));
        } else {
            $deck->setCommander(null);
        }

        $partnerName = trim($form->get('partner')->getData() ?? '');
        if ($partnerName) {
            $partnerColorLetters = $this->parseLetters($form->get('partner_colors')->getData());
            $deck->setPartner($commanderService->findOrCreate(
                $partnerName,
                $partnerColorLetters,
                null,
                null,
                $form->get('partner_image_uri')->getData() ?: null,
            ));
        } else {
            $deck->setPartner(null);
        }
    }

    private function parseLetters(?string $csv): array
    {
        if (!$csv) return [];
        return array_values(array_filter(array_map('trim', explode(',', $csv))));
    }
}
