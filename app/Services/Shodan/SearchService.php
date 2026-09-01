<?php

namespace App\Services\Shodan;

use App\Models\Search;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Runs a search against shodan.io and persists it as a permanent, immutable
 * record — every call creates a brand new Search + SearchRanking rows,
 * never updates an existing one. Re-running the same query later produces a
 * new snapshot in time rather than overwriting the previous one.
 */
class SearchService
{
    public function __construct(
        private readonly SearchScraper $scraper,
        private readonly HostSnapshotService $hostSnapshotService,
    ) {}

    public function search(string $query): Search
    {
        $data = $this->scraper->fetch($query);

        $search = DB::transaction(function () use ($query, $data) {
            $search = Search::create([
                'query' => $query,
                'total_results' => $data->totalResults,
                'searched_at' => now(),
            ]);

            $search->rankings()->createMany(
                collect($data->rankings)
                    ->map(fn ($ranking) => [
                        'type' => $ranking->type,
                        'label' => $ranking->label,
                        'count' => $ranking->count,
                    ])
                    ->all()
            );

            return $search;
        });

        $this->attachResultLocations($search, $data->resultIps);

        return $search->load(['rankings', 'hostSnapshots.host']);
    }

    /**
     * For each individually listed result, fetch (or reuse, per the usual
     * cooldown) its host page for its exact GPS coordinates. This is a
     * best-effort enrichment: the search itself is already archived above,
     * so one host page failing to scrape must not lose the whole search —
     * it's simply left off the results map.
     *
     * @param  array<int, string>  $ips
     */
    private function attachResultLocations(Search $search, array $ips): void
    {
        foreach ($ips as $position => $ip) {
            try {
                $snapshot = $this->hostSnapshotService->getOrFetch($ip);
            } catch (Throwable) {
                continue;
            }

            $search->hostSnapshots()->attach($snapshot->id, ['position' => $position]);
        }
    }
}
