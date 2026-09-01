<?php

namespace Tests\Unit\Services\Shodan;

use App\Enums\RankingType;
use App\Services\Shodan\SearchScraper;
use App\Services\Shodan\ShodanHttpClient;
use PHPUnit\Framework\TestCase;

class SearchScraperTest extends TestCase
{
    private SearchScraper $scraper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scraper = new SearchScraper(new ShodanHttpClient);
    }

    public function test_it_parses_the_total_results_count(): void
    {
        $data = $this->scraper->parse($this->fixture());

        $this->assertSame(13_464_341, $data->totalResults);
    }

    public function test_it_parses_all_five_ranking_groups(): void
    {
        $data = $this->scraper->parse($this->fixture());

        $types = collect($data->rankings)->map(fn ($ranking) => $ranking->type)->unique()->values();

        $this->assertEqualsCanonicalizing(RankingType::cases(), $types->all());
    }

    public function test_it_parses_the_top_countries_with_labels_and_counts(): void
    {
        $data = $this->scraper->parse($this->fixture());

        $countries = collect($data->rankings)->filter(fn ($ranking) => $ranking->type === RankingType::Country)->values();

        $this->assertCount(5, $countries);
        $this->assertSame('United States', $countries->first()->label);
        $this->assertSame(3_774_044, $countries->first()->count);
    }

    public function test_it_parses_ports_as_the_raw_port_number_label(): void
    {
        $data = $this->scraper->parse($this->fixture());

        $ports = collect($data->rankings)->filter(fn ($ranking) => $ranking->type === RankingType::Port)->values();

        $this->assertSame('80', $ports->first()->label);
        $this->assertSame(5_238_349, $ports->first()->count);
    }

    public function test_it_raises_a_clear_error_when_filters_require_a_logged_in_session(): void
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

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Please log in to use search filters.');

        $this->scraper->parse($html);
    }

    private function fixture(): string
    {
        return file_get_contents(__DIR__.'/../../../Fixtures/shodan_search_apache.html');
    }
}
