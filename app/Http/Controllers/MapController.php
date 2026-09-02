<?php

namespace App\Http\Controllers;

use App\Models\HostSnapshot;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MapController extends Controller
{
    /**
     * A single, cumulative map over every host ever scraped by any past
     * search or host lookup — filterable by country/city/port on the data
     * already stored locally. This never contacts shodan.io: it is a pure
     * replay of what free-text scraping has collected so far, which is how
     * this app achieves country/port filtering without ever needing a
     * connected Shodan account.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
        ]);

        $latestSnapshotIds = HostSnapshot::query()
            ->selectRaw('MAX(id)')
            ->groupBy('host_id');

        $hosts = HostSnapshot::query()
            ->whereIn('id', $latestSnapshotIds)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($filters['country'] ?? null, fn ($query, $country) => $query->where('country', 'like', "%{$country}%"))
            ->when($filters['city'] ?? null, fn ($query, $city) => $query->where('city', 'like', "%{$city}%"))
            ->when($filters['port'] ?? null, fn ($query, $port) => $query->whereJsonContains('open_ports', (int) $port))
            ->with('host')
            ->get();

        return view('map.index', [
            'hosts' => $hosts,
            'filters' => $filters,
        ]);
    }
}
