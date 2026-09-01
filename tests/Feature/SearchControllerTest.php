<?php

namespace Tests\Feature;

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

    public function test_filled_filter_fields_are_composed_into_the_shodan_query_syntax(): void
    {
        Http::fake(['shodan.io/*' => Http::response($this->fixture(), 200)]);

        $this->post(route('searches.store'), [
            'query' => 'apache',
            'country' => 'France',
            'port' => '22',
            'org' => 'Some "Quoted" Org',
        ]);

        Http::assertSent(function (Request $request) {
            return $request['query'] === 'apache country:"France" org:"Some Quoted Org" port:22';
        });
    }

    public function test_empty_filter_fields_are_left_out_of_the_query(): void
    {
        Http::fake(['shodan.io/*' => Http::response($this->fixture(), 200)]);

        $this->post(route('searches.store'), ['query' => 'apache']);

        Http::assertSent(fn (Request $request) => $request['query'] === 'apache');
    }

    private function fixture(): string
    {
        return file_get_contents(base_path('tests/Fixtures/shodan_search_apache.html'));
    }
}
