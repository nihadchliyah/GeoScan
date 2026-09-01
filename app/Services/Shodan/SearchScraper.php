<?php

namespace App\Services\Shodan;

use App\Enums\RankingType;
use App\Services\Shodan\Data\SearchPageData;
use App\Services\Shodan\Data\SearchRankingData;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Fetches and parses a shodan.io/search results page.
 *
 * Parsing (parse()) is kept separate from fetching (fetch()) so tests can
 * exercise the DOM extraction against a saved HTML fixture without ever
 * making a network call.
 */
class SearchScraper
{
    public function __construct(private readonly ShodanHttpClient $client) {}

    public function fetch(string $query): SearchPageData
    {
        $response = $this->client->get('/search', ['query' => $query]);
        $response->throw();

        return $this->parse($response->body());
    }

    public function parse(string $html): SearchPageData
    {
        $crawler = new Crawler($html);
        $summary = $crawler->filter('.summary');

        if ($summary->count() === 0) {
            throw new RuntimeException($this->explainMissingSummary($crawler));
        }

        return new SearchPageData(
            totalResults: $this->parseTotalResults($summary),
            rankings: $this->parseRankings($summary),
            resultIps: $this->parseResultIps($crawler),
        );
    }

    /**
     * Shodan renders an `.alert-error` banner instead of results for
     * several anonymous-visitor cases (search filters like `country:` or
     * `org:` requiring a logged-in session being the most common one).
     * Surface that message directly rather than a generic parsing error.
     */
    private function explainMissingSummary(Crawler $crawler): string
    {
        $alert = $crawler->filter('.alert-error');

        if ($alert->count() > 0) {
            $message = trim(preg_replace('/\s+/', ' ', $alert->text()));
            $message = preg_replace('/^Error:\s*/i', '', $message);

            return "Shodan a refusé cette recherche : \"{$message}\". Essaie une requête libre, sans filtre (ex. \"apache\").";
        }

        return 'Impossible de lire la page de résultats Shodan (structure de page changée, ou réponse inattendue).';
    }

    private function parseTotalResults(Crawler $summary): int
    {
        $text = $summary->filter('.total-results')->first()->text();

        return (int) preg_replace('/[^\d]/', '', $text);
    }

    /**
     * @return array<int, SearchRankingData>
     */
    private function parseRankings(Crawler $summary): array
    {
        $headings = $summary->filter('h6');
        $lists = $summary->filter('ul.facet-list');
        $typeByLabel = array_flip(RankingType::labels());

        $rankings = [];
        $listIndex = 0;

        foreach ($headings as $headingNode) {
            $headingText = trim($headingNode->textContent);
            $type = $typeByLabel[$headingText] ?? null;

            if ($type === null || $listIndex >= $lists->count()) {
                continue;
            }

            foreach ($this->parseFacetList($lists->eq($listIndex)) as $item) {
                $rankings[] = new SearchRankingData(
                    type: RankingType::from($type),
                    label: $item['label'],
                    count: $item['count'],
                );
            }

            $listIndex++;
        }

        return $rankings;
    }

    /**
     * Each listed result links its IP via `/host/{ip}` in its heading —
     * the only place shodan.io exposes individual result IPs on the
     * anonymous, paginated search page (typically up to 10 per page).
     *
     * @return array<int, string>
     */
    private function parseResultIps(Crawler $crawler): array
    {
        $ips = [];

        foreach ($crawler->filter('.results .result .heading a.title') as $node) {
            $href = (new Crawler($node))->attr('href');

            if ($href !== null && preg_match('#^/host/([\d.]+)$#', $href, $matches)) {
                $ips[] = $matches[1];
            }
        }

        return $ips;
    }

    /**
     * @return array<int, array{label: string, count: int}>
     */
    private function parseFacetList(Crawler $list): array
    {
        $items = [];

        foreach ($list->filter('li') as $liNode) {
            $li = new Crawler($liNode);
            $spans = $li->filter('span');

            if ($spans->count() === 0) {
                continue; // the trailing "More..." link has no count span
            }

            $items[] = [
                'label' => trim($li->filter('a')->first()->text()),
                'count' => (int) preg_replace('/[^\d]/', '', $spans->first()->text()),
            ];
        }

        return $items;
    }
}
