<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSearchRequest;
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
     * Show the search form.
     */
    public function create(): View
    {
        return view('searches.create');
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
