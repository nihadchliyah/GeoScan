<?php

namespace Tests\Feature;

use App\Jobs\FetchSearchResultLocationJob;
use App\Models\Search;
use App\Services\Shodan\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_one_result_location_job_per_listed_result_instead_of_fetching_them_inline(): void
    {
        Queue::fake();
        Http::fake(['shodan.io/search*' => Http::response($this->searchFixture(), 200)]);

        $search = $this->app->make(SearchService::class)->search('apache');

        // The search itself is available immediately — no /host/{ip} calls
        // block the request, they're only queued.
        Http::assertSentCount(1);
        $this->assertSame(10, $search->expected_result_count);
        $this->assertCount(0, $search->hostSnapshots);

        Queue::assertPushed(FetchSearchResultLocationJob::class, 10);
        Queue::assertPushed(fn (FetchSearchResultLocationJob $job) => $job->search->is($search)
            && $job->position === 0
        );
    }

    public function test_it_persists_a_search_and_its_rankings_without_ever_calling_the_real_site(): void
    {
        $this->fakeShodan();

        $search = $this->app->make(SearchService::class)->search('apache');

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/search') && $request['query'] === 'apache');

        $this->assertDatabaseCount('searches', 1);
        $this->assertSame('apache', $search->query);
        $this->assertSame(13_464_341, $search->total_results);
        $this->assertCount(25, $search->rankings); // 5 types x 5 entries
    }

    public function test_it_also_fetches_the_exact_location_of_every_individually_listed_result(): void
    {
        $this->fakeShodan();

        $search = $this->app->make(SearchService::class)->search('apache');

        // The fixture lists 10 individual results, each a brand new host —
        // one /search request plus one /host/{ip} request per result.
        Http::assertSentCount(11);
        $this->assertDatabaseCount('hosts', 10);
        $this->assertDatabaseCount('host_snapshots', 10);
        $this->assertCount(10, $search->hostSnapshots);

        $first = $search->hostSnapshots->first();
        $this->assertSame(38.00881, $first->latitude);
        $this->assertSame(-122.11746, $first->longitude);
    }

    public function test_a_result_whose_host_page_fails_to_scrape_does_not_break_the_search(): void
    {
        Http::fake([
            'shodan.io/search*' => Http::response($this->searchFixture(), 200),
            'shodan.io/host/*' => Http::response('<html><body>not a host page</body></html>', 200),
        ]);

        $search = $this->app->make(SearchService::class)->search('apache');

        $this->assertDatabaseCount('searches', 1);
        $this->assertCount(0, $search->hostSnapshots);
    }

    public function test_running_the_same_query_twice_creates_two_separate_archived_searches(): void
    {
        $this->fakeShodan();

        $service = $this->app->make(SearchService::class);
        $service->search('apache');
        $service->search('apache');

        $this->assertDatabaseCount('searches', 2);
        // Second run's 10 host pages are all within cooldown and reused:
        // (1 search + 10 hosts) + (1 search + 0 hosts, reused) = 12.
        Http::assertSentCount(12);
        $this->assertDatabaseCount('host_snapshots', 10);
    }

    public function test_viewing_an_archived_search_never_triggers_a_new_http_request(): void
    {
        $this->fakeShodan();

        $search = $this->app->make(SearchService::class)->search('apache');
        $requestsMadeBySearch = count(Http::recorded());

        $response = $this->get(route('searches.show', $search));

        $response->assertOk();
        Http::assertSentCount($requestsMadeBySearch); // none from show()
    }

    private function fakeShodan(): void
    {
        Http::fake([
            'shodan.io/search*' => Http::response($this->searchFixture(), 200),
            'shodan.io/host/*' => Http::response($this->hostFixture(), 200),
        ]);
    }

    private function searchFixture(): string
    {
        return file_get_contents(base_path('tests/Fixtures/shodan_search_apache.html'));
    }

    private function hostFixture(): string
    {
        return file_get_contents(base_path('tests/Fixtures/shodan_host_8_8_8_8.html'));
    }
}
