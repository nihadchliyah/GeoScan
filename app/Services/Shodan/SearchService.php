<?php

namespace App\Services\Shodan;

use App\Jobs\FetchSearchResultLocationJob;
use App\Models\Search;
use Illuminate\Support\Facades\DB;

/**
 * Runs a search against shodan.io and persists it as a permanent, immutable
 * record — every call creates a brand new Search + SearchRanking rows,
 * never updates an existing one. Re-running the same query later produces a
 * new snapshot in time rather than overwriting the previous one.
 */
class SearchService
{
    public function __construct(private readonly SearchScraper $scraper) {}

    public function search(string $query): Search
    {
        $data = $this->scraper->fetch($query);

        $search = DB::transaction(function () use ($query, $data) {
            $search = Search::create([
                'query' => $query,
                'total_results' => $data->totalResults,
                'expected_result_count' => count($data->resultIps),
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

        $this->dispatchResultLocationJobs($search, $data->resultIps);

        return $search->load(['rankings', 'hostSnapshots.host']);
    }

    /**
     * For each individually listed result, queue a job that fetches (or
     * reuses, per the usual cooldown) its host page for its exact GPS
     * coordinates. Queued rather than fetched here so the search itself is
     * archived and shown immediately, instead of the request blocking on
     * shodan.io's crawl-delay for every one of these one by one.
     *
     * @param  array<int, string>  $ips
     */
    private function dispatchResultLocationJobs(Search $search, array $ips): void
    {
        foreach ($ips as $position => $ip) {
            FetchSearchResultLocationJob::dispatch($search, $ip, $position);
        }
    }
}
