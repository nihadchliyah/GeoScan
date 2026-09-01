<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSearchRequest;
use App\Models\Search;
use App\Services\Shodan\SearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
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
            $search = $this->searchService->search($request->composedQuery());
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['query' => $exception->getMessage()]);
        }

        return redirect()->route('searches.show', $search);
    }

    /**
     * List every search ever run, most recent first.
     */
    public function index(): View
    {
        $searches = Search::query()->latest('searched_at')->paginate(20);

        return view('searches.index', ['searches' => $searches]);
    }

    /**
     * Replay a stored search exactly as it was recorded — this never
     * contacts shodan.io again.
     */
    public function show(Search $search): View
    {
        $search->load('rankings');

        return view('searches.show', ['search' => $search]);
    }
}
