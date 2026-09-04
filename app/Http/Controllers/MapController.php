<?php

namespace App\Http\Controllers;

use App\Models\HostSnapshot;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MapController extends Controller
{
    /**
     * A single, cumulative map over every host ever scraped by any past
     * search or host lookup — filterable on every field a host snapshot
     * carries, all local. This never contacts shodan.io: it is a pure
     * replay of what free-text scraping has collected so far, which is how
     * this app achieves Shodan-style filtering without ever needing a
     * connected account.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'organization' => ['nullable', 'string', 'max:150'],
            'isp' => ['nullable', 'string', 'max:150'],
            'asn' => ['nullable', 'string', 'max:20'],
            'product' => ['nullable', 'string', 'max:150'],
            'hostname' => ['nullable', 'string', 'max:150'],
        ]);

        $latestSnapshotIds = HostSnapshot::query()
            ->selectRaw('MAX(id)')
            ->groupBy('host_id');

        $hosts = HostSnapshot::query()
            ->whereIn('id', $latestSnapshotIds)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($filters['country'] ?? null, fn ($query, $value) => $query->where('country', 'like', "%{$value}%"))
            ->when($filters['city'] ?? null, fn ($query, $value) => $query->where('city', 'like', "%{$value}%"))
            ->when($filters['port'] ?? null, fn ($query, $value) => $query->whereJsonContains('open_ports', (int) $value))
            ->when($filters['organization'] ?? null, fn ($query, $value) => $query->where('organization', 'like', "%{$value}%"))
            ->when($filters['isp'] ?? null, fn ($query, $value) => $query->where('isp', 'like', "%{$value}%"))
            ->when($filters['asn'] ?? null, fn ($query, $value) => $query->where('asn', 'like', "%{$value}%"))
            ->when($filters['product'] ?? null, fn ($query, $value) => $query->where('web_technologies', 'like', "%{$value}%"))
            ->when($filters['hostname'] ?? null, fn ($query, $value) => $query->where(fn ($q) => $q->where('hostnames', 'like', "%{$value}%")->orWhere('domains', 'like', "%{$value}%")))
            ->with('host')
            ->get();

        return view('map.index', [
            'hosts' => $hosts,
            'filters' => $filters,
        ]);
    }
}
