<?php

namespace Tests\Unit\Services\Shodan;

use App\Services\Shodan\HostScraper;
use App\Services\Shodan\ShodanHttpClient;
use PHPUnit\Framework\TestCase;

class HostScraperTest extends TestCase
{
    private HostScraper $scraper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scraper = new HostScraper(new ShodanHttpClient);
    }

    public function test_it_parses_general_information(): void
    {
        $data = $this->scraper->parse($this->fixture());

        $this->assertSame('8.8.8.8', $data->ip);
        $this->assertSame('United States', $data->country);
        $this->assertSame('Mountain View', $data->city);
        $this->assertSame('Google LLC', $data->organization);
        $this->assertSame('Google LLC', $data->isp);
        $this->assertSame('AS15169', $data->asn);
    }

    public function test_it_parses_hostnames_and_domains(): void
    {
        $data = $this->scraper->parse($this->fixture());

        $this->assertSame(['dns.google', 'web.medonecapital.com'], $data->hostnames);
        $this->assertSame(['dns.google', 'medonecapital.com'], $data->domains);
    }

    public function test_it_parses_web_technologies(): void
    {
        $data = $this->scraper->parse($this->fixture());

        $this->assertSame(['HTTP/3', 'HSTS'], $data->webTechnologies);
    }

    public function test_it_parses_unique_open_ports_as_integers(): void
    {
        $data = $this->scraper->parse($this->fixture());

        $this->assertSame([53, 443], $data->openPorts);
    }

    public function test_it_parses_the_last_seen_date(): void
    {
        $data = $this->scraper->parse($this->fixture());

        $this->assertNotNull($data->lastSeenAt);
        $this->assertSame('2026-08-31', $data->lastSeenAt->toDateString());
    }

    public function test_it_parses_the_coordinates_embedded_in_the_map_script(): void
    {
        $data = $this->scraper->parse($this->fixture());

        $this->assertSame(38.00881, $data->latitude);
        $this->assertSame(-122.11746, $data->longitude);
    }

    public function test_coordinates_are_null_when_the_page_has_no_map(): void
    {
        $data = $this->scraper->parse('<html><body><h2 class="host-title">1.2.3.4</h2></body></html>');

        $this->assertNull($data->latitude);
        $this->assertNull($data->longitude);
    }

    private function fixture(): string
    {
        return file_get_contents(__DIR__.'/../../../Fixtures/shodan_host_8_8_8_8.html');
    }
}
