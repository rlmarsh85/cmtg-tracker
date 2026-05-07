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
    // If a key were required by another provider, add it to .env.local (already gitignored):
    //   SOME_API_KEY=your_key_here
    // then inject it: #[Autowire(env: 'SOME_API_KEY')] string $apiKey

    // ── Commander name search (used for the commander autocomplete) ──────────
    #[Route('/api/commanders/search', name: 'commander_search', methods: ['GET'])]
    public function search(Request $request, HttpClientInterface $client): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 2) return $this->json([]);

        try {
            $response = $client->request('GET', 'https://api.scryfall.com/cards/search', [
                'query'   => ['q' => 'name:' . $q . ' is:commander', 'order' => 'name', 'unique' => 'names'],
                'headers' => ['Accept' => 'application/json', 'User-Agent' => 'MTGTracker/1.0'],
                'timeout' => 5,
            ]);

            if ($response->getStatusCode() !== 200) return $this->json([]);

            $data = $response->toArray();
            $results = array_map(function ($card) {
                $pi = $this->extractPartnerInfo($card);
                return [
                    'name'           => $card['name'],
                    'color_identity' => $card['color_identity'] ?? [],
                    'partner_type'   => $pi['partner_type'],
                    'partner_with'   => $pi['partner_with'],
                    'image_uri'      => $this->extractImageUri($card),
                ];
            }, $data['data'] ?? []);

            return $this->json(array_slice($results, 0, 12));
        } catch (\Exception) {
            return $this->json([]);
        }
    }

    // ── Partner search (filtered by partner type) ────────────────────────────
    #[Route('/api/commanders/partners', name: 'partner_search', methods: ['GET'])]
    public function partnerSearch(Request $request, HttpClientInterface $client): JsonResponse
    {
        $q    = trim($request->query->get('q', ''));
        $type = $request->query->get('type', 'partner');

        if (strlen($q) < 2) return $this->json([]);
        if ($type === 'partner_with') return $this->json([]);   // fixed partner, no search

        $filter = match ($type) {
            'friends_forever'   => 'keyword:"friends forever" is:commander',
            'choose_background' => 'is:background',
            'doctors_companion' => 'keyword:"doctor\'s companion" is:commander',
            default             => 'keyword:partner is:commander',
        };

        try {
            $response = $client->request('GET', 'https://api.scryfall.com/cards/search', [
                'query'   => ['q' => 'name:' . $q . ' ' . $filter, 'order' => 'name', 'unique' => 'names'],
                'headers' => ['Accept' => 'application/json', 'User-Agent' => 'MTGTracker/1.0'],
                'timeout' => 5,
            ]);

            if ($response->getStatusCode() !== 200) return $this->json([]);

            $data = $response->toArray();
            $results = array_map(fn($card) => [
                'name'           => $card['name'],
                'color_identity' => $card['color_identity'] ?? [],
                'image_uri'      => $this->extractImageUri($card),
            ], $data['data'] ?? []);

            return $this->json(array_slice($results, 0, 12));
        } catch (\Exception) {
            return $this->json([]);
        }
    }

    // ── Exact card lookup (used to initialise state on edit and for partner_with) ──
    #[Route('/api/commanders/info', name: 'commander_info', methods: ['GET'])]
    public function commanderInfo(Request $request, HttpClientInterface $client): JsonResponse
    {
        $name = trim($request->query->get('name', ''));
        if (empty($name)) return $this->json(null, 404);

        try {
            $response = $client->request('GET', 'https://api.scryfall.com/cards/named', [
                'query'   => ['exact' => $name],
                'headers' => ['Accept' => 'application/json', 'User-Agent' => 'MTGTracker/1.0'],
                'timeout' => 5,
            ]);

            if ($response->getStatusCode() !== 200) return $this->json(null, 404);

            $card = $response->toArray();
            $pi   = $this->extractPartnerInfo($card);

            return $this->json([
                'name'           => $card['name'],
                'color_identity' => $card['color_identity'] ?? [],
                'partner_type'   => $pi['partner_type'],
                'partner_with'   => $pi['partner_with'],
                'image_uri'      => $this->extractImageUri($card),
            ]);
        } catch (\Exception) {
            return $this->json(null, 404);
        }
    }

    // ── Card image extraction (normal face; DFC uses front face) ────────────
    private function extractImageUri(array $card): ?string
    {
        return $card['image_uris']['normal']
            ?? $card['card_faces'][0]['image_uris']['normal']
            ?? null;
    }

    // ── Partner ability detection ────────────────────────────────────────────
    private function extractPartnerInfo(array $card): array
    {
        // Merge keywords and oracle text across card faces for DFCs
        $keywords = $card['keywords'] ?? [];
        if (empty($keywords) && isset($card['card_faces'])) {
            foreach ($card['card_faces'] as $face) {
                $keywords = array_merge($keywords, $face['keywords'] ?? []);
            }
            $keywords = array_values(array_unique($keywords));
        }
        $keywords = array_map('strtolower', $keywords);

        $oracleText = $card['oracle_text'] ?? '';
        if (empty($oracleText) && isset($card['card_faces'])) {
            $oracleText = implode("\n", array_column($card['card_faces'], 'oracle_text'));
        }

        // "Partner with X" must be checked BEFORE generic "partner" to avoid false positives
        if (in_array('partner with', $keywords) || preg_match('/\bPartner with \S/i', $oracleText)) {
            if (preg_match('/\bPartner with ([^\n(]+?)(?:\s*\(|$)/m', $oracleText, $m)) {
                return ['partner_type' => 'partner_with', 'partner_with' => trim($m[1])];
            }
            return ['partner_type' => 'partner_with', 'partner_with' => null];
        }

        if (in_array('partner', $keywords)) {
            return ['partner_type' => 'partner', 'partner_with' => null];
        }

        if (in_array('friends forever', $keywords)) {
            return ['partner_type' => 'friends_forever', 'partner_with' => null];
        }

        if (in_array('choose a background', $keywords)) {
            return ['partner_type' => 'choose_background', 'partner_with' => null];
        }

        if (in_array("doctor's companion", $keywords)) {
            return ['partner_type' => 'doctors_companion', 'partner_with' => null];
        }

        return ['partner_type' => null, 'partner_with' => null];
    }
}
