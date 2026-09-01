<?php

namespace App\Http\Controllers;

use App\Services\Shodan\HostSnapshotService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class HostController extends Controller
{
    public function __construct(private readonly HostSnapshotService $hostSnapshotService) {}

    /**
     * Show a host's current snapshot plus the timeline of every previous
     * one. A fresh snapshot is scraped only if the cooldown has expired.
     */
    public function show(string $ip): View|RedirectResponse
    {
        try {
            $snapshot = $this->hostSnapshotService->getOrFetch($ip);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $snapshot->loadMissing('host.snapshots');

        return view('hosts.show', [
            'host' => $snapshot->host,
            'snapshot' => $snapshot,
            'timeline' => $snapshot->host->snapshots->sortByDesc('fetched_at')->values(),
        ]);
    }
}
