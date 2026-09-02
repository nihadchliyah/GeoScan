<?php

namespace App\Jobs;

use App\Models\Search;
use App\Services\Shodan\HostSnapshotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Fetches one search result's host page in the background so the search
 * itself can be archived and shown immediately — see SearchService, which
 * dispatches one of these per listed result instead of fetching them
 * synchronously inside the HTTP request.
 */
class FetchSearchResultLocationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Search $search,
        public readonly string $ip,
        public readonly int $position,
    ) {}

    public function handle(HostSnapshotService $hostSnapshotService): void
    {
        try {
            $snapshot = $hostSnapshotService->getOrFetch($this->ip);
        } catch (Throwable) {
            return;
        }

        $this->search->hostSnapshots()->syncWithoutDetaching([
            $snapshot->id => ['position' => $this->position],
        ]);
    }
}
