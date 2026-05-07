<?php

namespace App\Command;

use App\Repository\CommanderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'app:backfill-commander-images', description: 'Fetch missing image URIs for commanders from Scryfall')]
class BackfillCommanderImagesCommand extends Command
{
    public function __construct(
        private CommanderRepository $commanderRepository,
        private EntityManagerInterface $em,
        private HttpClientInterface $http,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $commanders = $this->commanderRepository->findBy(['imageUri' => null]);

        if (empty($commanders)) {
            $io->success('All commanders already have image URIs.');
            return Command::SUCCESS;
        }

        $io->progressStart(count($commanders));

        foreach ($commanders as $commander) {
            try {
                $response = $this->http->request('GET', 'https://api.scryfall.com/cards/named', [
                    'query'   => ['exact' => $commander->getName()],
                    'headers' => ['Accept' => 'application/json', 'User-Agent' => 'MTGTracker/1.0'],
                    'timeout' => 8,
                ]);

                if ($response->getStatusCode() === 200) {
                    $card = $response->toArray();
                    $uri = $card['image_uris']['normal']
                        ?? $card['card_faces'][0]['image_uris']['normal']
                        ?? null;

                    if ($uri) {
                        $commander->setImageUri($uri);
                        $io->writeln(" <info>✓</info> {$commander->getName()}");
                    } else {
                        $io->writeln(" <comment>?</comment> {$commander->getName()} — no normal image found");
                    }
                } else {
                    $io->writeln(" <error>✗</error> {$commander->getName()} — HTTP {$response->getStatusCode()}");
                }
            } catch (\Exception $e) {
                $io->writeln(" <error>✗</error> {$commander->getName()} — {$e->getMessage()}");
            }

            $io->progressAdvance();
            usleep(110_000); // ~9 req/s, under Scryfall's 10/s limit
        }

        $io->progressFinish();
        $this->em->flush();
        $io->success(sprintf('Done. Processed %d commander(s).', count($commanders)));
        return Command::SUCCESS;
    }
}
