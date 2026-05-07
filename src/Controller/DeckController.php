<?php

namespace App\Controller;

use App\Entity\Deck;
use App\Form\DeckType;
use App\Repository\ColorIdentityRepository;
use App\Repository\DeckRepository;
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
    public function new(Request $request, EntityManagerInterface $em, ColorIdentityRepository $ciRepo): Response
    {
        $deck = new Deck();
        $form = $this->createForm(DeckType::class, $deck);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $deck->setColorIdentity($ciRepo->findByColorNames($form->get('colors')->getData() ?? []));
            if ($deck->getFormat() !== 'Commander') {
                $deck->setCommander(null);
                $deck->setPartner(null);
            }
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
    public function edit(Request $request, Deck $deck, EntityManagerInterface $em, ColorIdentityRepository $ciRepo): Response
    {
        $form = $this->createForm(DeckType::class, $deck);

        if ($deck->getColorIdentity()) {
            $existing = $deck->getColorIdentity()->getColors()->map(fn($c) => $c->getName())->toArray();
            $form->get('colors')->setData($existing);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $deck->setColorIdentity($ciRepo->findByColorNames($form->get('colors')->getData() ?? []));
            if ($deck->getFormat() !== 'Commander') {
                $deck->setCommander(null);
                $deck->setPartner(null);
            }
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
}
