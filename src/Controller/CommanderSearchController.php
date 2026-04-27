<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CommanderSearchController extends AbstractController
{
    // Scryfall is a free, open API — no key needed.
    // If a key were required, add it to .env.local (already gitignored):
    //   SCRYFALL_API_KEY=your_key_here
    // then inject it via: #[Autowire(env: 'SCRYFALL_API_KEY')] string $apiKey

    #[Route('/api/commanders/search', name: 'commander_search', methods: ['GET'])]
    public function search(Request $request, HttpClientInterface $client): JsonResponse
    {
        $q = trim($request->query->get('q', ''));

        if (strlen($q) < 2) {
            return $this->json([]);
        }

        try {
            $response = $client->request('GET', 'https://api.scryfall.com/cards/search', [
                'query' => [
                    'q'      => 'name:' . $q . ' is:commander',
                    'order'  => 'name',
                    'unique' => 'names',
                ],
                'headers' => [
                    'Accept'     => 'application/json',
                    'User-Agent' => 'MTGTracker/1.0',
                ],
                'timeout' => 5,
            ]);

            if ($response->getStatusCode() !== 200) {
                return $this->json([]);
            }

            $data = $response->toArray();

            $commanders = array_map(fn($card) => [
                'name'           => $card['name'],
                'color_identity' => $card['color_identity'] ?? [],
            ], $data['data'] ?? []);

            return $this->json(array_slice($commanders, 0, 12));
        } catch (\Exception) {
            return $this->json([]);
        }
    }
}
