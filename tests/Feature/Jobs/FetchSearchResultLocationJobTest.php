<?php

namespace Tests\Feature\Jobs;

use App\Jobs\FetchSearchResultLocationJob;
use App\Models\Search;
use App\Services\Shodan\HostSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchSearchResultLocationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fetches_the_host_page_and_attaches_its_snapshot_at_the_given_position(): void
    {
        Http::fake(['shodan.io/host/*' => Http::response($this->hostFixture(), 200)]);

        $search = Search::factory()->create(['expected_result_count' => 1]);

        (new FetchSearchResultLocationJob($search, '8.8.8.8', 3))->handle($this->app->make(HostSnapshotService::class));

        $search->refresh()->load('hostSnapshots');

        $this->assertCount(1, $search->hostSnapshots);
        $this->assertSame('8.8.8.8', $search->hostSnapshots->first()->host->ip);
        $this->assertSame(3, $search->hostSnapshots->first()->pivot->position);
    }

    public function test_a_host_page_that_fails_to_scrape_is_left_off_the_search_without_throwing(): void
    {
        Http::fake(['shodan.io/host/*' => Http::response('<html><body>not a host page</body></html>', 200)]);

        $search = Search::factory()->create(['expected_result_count' => 1]);

        (new FetchSearchResultLocationJob($search, '8.8.8.8', 0))->handle($this->app->make(HostSnapshotService::class));

        $this->assertCount(0, $search->refresh()->hostSnapshots);
    }

    private function hostFixture(): string
    {
        return file_get_contents(base_path('tests/Fixtures/shodan_host_8_8_8_8.html'));
    }
}
