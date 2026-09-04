<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSearchRequest;
use App\Models\HostSnapshot;
use App\Models\Search;
use App\Services\Shodan\SearchService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $searchService) {}

    /**
     * Show the search form, plus a map of every host scraped so far,
     * filterable on every field a host snapshot carries. This local map is
     * how the app offers Shodan-style filtering (country/port/org/...)
     * without ever needing a connected account: filters apply to what
     * free-text scraping has already collected, not to a new request.
     */
    public function create(Request $request): View
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

        return view('searches.create', [
            'hosts' => $hosts,
            'filters' => $filters,
        ]);
    }

    /**
     * Run a fresh search against shodan.io and store it.
     */
    public function store(StoreSearchRequest $request): RedirectResponse
    {
        try {
            $search = $this->searchService->search($request->validated('query'));
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['query' => $exception->getMessage()]);
        }

        return redirect()->route('searches.show', $search);
    }

    /**
     * List every search ever run, most recent first. Optionally narrowed to
     * a precise date/time range (down to the second) via `from`/`to`.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $searches = Search::query()
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('searched_at', '>=', Carbon::parse($from)))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('searched_at', '<=', Carbon::parse($to)))
            ->latest('searched_at')
            ->paginate(20)
            ->withQueryString();

        return view('searches.index', ['searches' => $searches]);
    }

    /**
     * Replay a stored search exactly as it was recorded — this never
     * contacts shodan.io again.
     */
    public function show(Search $search): View
    {
        $search->load(['rankings', 'hostSnapshots.host']);

        return view('searches.show', ['search' => $search]);
    }
}
