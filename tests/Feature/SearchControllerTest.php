<?php

namespace Tests\Feature;

use App\Models\Search;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_query_shodan_refuses_shows_a_friendly_error_instead_of_crashing(): void
    {
        $html = <<<'HTML'
            <html><body>
                <div class="alert alert-error">
                    <div><em>Error:</em>
                        <p>Please log in to use search filters.</p>
                    </div>
                </div>
            </body></html>
            HTML;

        Http::fake(['shodan.io/*' => Http::response($html, 200)]);

        $response = $this->from(route('searches.create'))
            ->post(route('searches.store'), ['query' => 'country:"FR"']);

        $response->assertRedirect(route('searches.create'));
        $response->assertSessionHasErrors('query');
        $this->assertDatabaseCount('searches', 0);
    }

    public function test_empty_filter_fields_are_left_out_of_the_query(): void
    {
        Http::fake(['shodan.io/*' => Http::response($this->fixture(), 200)]);

        $this->post(route('searches.store'), ['query' => 'apache']);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/search') && $request['query'] === 'apache');
    }

    public function test_the_history_can_be_narrowed_to_a_precise_date_time_range(): void
    {
        $before = Search::factory()->create(['query' => 'too-early', 'searched_at' => '2026-01-01 08:00:00']);
        $inRange = Search::factory()->create(['query' => 'in-range', 'searched_at' => '2026-01-01 12:30:15']);
        $after = Search::factory()->create(['query' => 'too-late', 'searched_at' => '2026-01-01 18:00:00']);

        $response = $this->get(route('searches.index', [
            'from' => '2026-01-01T12:00:00',
            'to' => '2026-01-01T13:00:00',
        ]));

        $response->assertOk();
        $response->assertSeeText($inRange->query);
        $response->assertDontSeeText($before->query);
        $response->assertDontSeeText($after->query);
    }

    public function test_an_invalid_date_filter_is_rejected(): void
    {
        $response = $this->get(route('searches.index', ['from' => 'not-a-date']));

        $response->assertSessionHasErrors('from');
    }

    private function fixture(): string
    {
        return file_get_contents(base_path('tests/Fixtures/shodan_search_apache.html'));
    }
}
