<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CommanderSearchController extends AbstractController
{
    public function __construct(private CacheInterface $cache) {}

    // ── Commander name search ────────────────────────────────────────────────
    #[Route('/api/commanders/search', name: 'commander_search', methods: ['GET'])]
    public function search(Request $request, HttpClientInterface $client): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 2) return $this->json([]);

        $key     = 'scryfall.search.' . md5(strtolower($q));
        $results = $this->cache->get($key, function (ItemInterface $item) use ($q, $client) {
            $item->expiresAfter(3600);
            try {
                $response = $client->request('GET', 'https://api.scryfall.com/cards/search', [
                    'query'   => ['q' => 'name:' . $q . ' is:commander', 'order' => 'name', 'unique' => 'names'],
                    'headers' => ['Accept' => 'application/json', 'User-Agent' => 'MTGTracker/1.0'],
                    'timeout' => 5,
                ]);
                if ($response->getStatusCode() !== 200) return [];
                $data = $response->toArray();
                return array_slice(array_map(function ($card) {
                    $pi = $this->extractPartnerInfo($card);
                    return [
                        'name'           => $card['name'],
                        'color_identity' => $card['color_identity'] ?? [],
                        'partner_type'   => $pi['partner_type'],
                        'partner_with'   => $pi['partner_with'],
                        'image_uri'      => $this->extractImageUri($card),
                    ];
                }, $data['data'] ?? []), 0, 12);
            } catch (\Exception) {
                return [];
            }
        });

        return $this->json($results);
    }

    // ── Partner search ───────────────────────────────────────────────────────
    #[Route('/api/commanders/partners', name: 'partner_search', methods: ['GET'])]
    public function partnerSearch(Request $request, HttpClientInterface $client): JsonResponse
    {
        $q    = trim($request->query->get('q', ''));
        $type = $request->query->get('type', 'partner');

        if (strlen($q) < 2) return $this->json([]);
        if ($type === 'partner_with') return $this->json([]);

        $key     = 'scryfall.partners.' . md5(strtolower($q)) . '.' . $type;
        $results = $this->cache->get($key, function (ItemInterface $item) use ($q, $type, $client) {
            $item->expiresAfter(3600);
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
                if ($response->getStatusCode() !== 200) return [];
                $data = $response->toArray();
                return array_slice(array_map(fn($card) => [
                    'name'           => $card['name'],
                    'color_identity' => $card['color_identity'] ?? [],
                    'image_uri'      => $this->extractImageUri($card),
                ], $data['data'] ?? []), 0, 12);
            } catch (\Exception) {
                return [];
            }
        });

        return $this->json($results);
    }

    // ── Exact card lookup ────────────────────────────────────────────────────
    #[Route('/api/commanders/info', name: 'commander_info', methods: ['GET'])]
    public function commanderInfo(Request $request, HttpClientInterface $client): JsonResponse
    {
        $name = trim($request->query->get('name', ''));
        if (empty($name)) return $this->json(null, 404);

        $key  = 'scryfall.info.' . md5(strtolower($name));
        $data = $this->cache->get($key, function (ItemInterface $item) use ($name, $client) {
            $item->expiresAfter(86400);
            try {
                $response = $client->request('GET', 'https://api.scryfall.com/cards/named', [
                    'query'   => ['exact' => $name],
                    'headers' => ['Accept' => 'application/json', 'User-Agent' => 'MTGTracker/1.0'],
                    'timeout' => 5,
                ]);
                if ($response->getStatusCode() !== 200) return null;
                $card = $response->toArray();
                $pi   = $this->extractPartnerInfo($card);
                return [
                    'name'           => $card['name'],
                    'color_identity' => $card['color_identity'] ?? [],
                    'partner_type'   => $pi['partner_type'],
                    'partner_with'   => $pi['partner_with'],
                    'image_uri'      => $this->extractImageUri($card),
                ];
            } catch (\Exception) {
                return null;
            }
        });

        if ($data === null) return $this->json(null, 404);
        return $this->json($data);
    }

    // ── Card image extraction ────────────────────────────────────────────────
    private function extractImageUri(array $card): ?string
    {
        return $card['image_uris']['normal']
            ?? $card['card_faces'][0]['image_uris']['normal']
            ?? null;
    }

    // ── Partner ability detection ────────────────────────────────────────────
    private function extractPartnerInfo(array $card): array
    {
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

        if (in_array('partner with', $keywords) || preg_match('/\bPartner with \S/i', $oracleText)) {
            if (preg_match('/\bPartner with ([^\n(]+?)(?:\s*\(|$)/m', $oracleText, $m)) {
                return ['partner_type' => 'partner_with', 'partner_with' => trim($m[1])];
            }
            return ['partner_type' => 'partner_with', 'partner_with' => null];
        }
        if (in_array('partner', $keywords))              return ['partner_type' => 'partner',           'partner_with' => null];
        if (in_array('friends forever', $keywords))      return ['partner_type' => 'friends_forever',   'partner_with' => null];
        if (in_array('choose a background', $keywords))  return ['partner_type' => 'choose_background', 'partner_with' => null];
        if (in_array("doctor's companion", $keywords))   return ['partner_type' => 'doctors_companion', 'partner_with' => null];

        return ['partner_type' => null, 'partner_with' => null];
    }
}
