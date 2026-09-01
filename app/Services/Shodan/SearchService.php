<?php

namespace App\Services\Shodan;

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

        return DB::transaction(function () use ($query, $data) {
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

            return $search->load('rankings');
        });
    }
}
