<?php

namespace Tests\Feature;

use App\Models\Search;
use App\Services\Shodan\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_a_search_and_its_rankings_without_ever_calling_the_real_site(): void
    {
        Http::fake([
            'shodan.io/*' => Http::response($this->fixture(), 200),
        ]);

        $search = $this->app->make(SearchService::class)->search('apache');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/search') && $request['query'] === 'apache');

        $this->assertDatabaseCount('searches', 1);
        $this->assertSame('apache', $search->query);
        $this->assertSame(13_464_341, $search->total_results);
        $this->assertCount(25, $search->rankings); // 5 types x 5 entries
    }

    public function test_running_the_same_query_twice_creates_two_separate_archived_searches(): void
    {
        Http::fake([
            'shodan.io/*' => Http::response($this->fixture(), 200),
        ]);

        $service = $this->app->make(SearchService::class);
        $service->search('apache');
        $service->search('apache');

        $this->assertDatabaseCount('searches', 2);
        Http::assertSentCount(2);
    }

    public function test_viewing_an_archived_search_never_triggers_a_new_http_request(): void
    {
        Http::fake([
            'shodan.io/*' => Http::response($this->fixture(), 200),
        ]);

        $search = $this->app->make(SearchService::class)->search('apache');

        $response = $this->get(route('searches.show', $search));

        $response->assertOk();
        Http::assertSentCount(1); // still just the one request made by search(), none from show()
    }

    private function fixture(): string
    {
        return file_get_contents(base_path('tests/Fixtures/shodan_search_apache.html'));
    }
}
