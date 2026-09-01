<?php

namespace Tests\Feature;

use App\Services\Shodan\HostSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HostSnapshotServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_host_and_a_snapshot_from_the_scraped_page(): void
    {
        Http::fake(['shodan.io/*' => Http::response($this->fixture(), 200)]);

        $snapshot = $this->app->make(HostSnapshotService::class)->getOrFetch('8.8.8.8');

        $this->assertDatabaseCount('hosts', 1);
        $this->assertDatabaseCount('host_snapshots', 1);
        $this->assertSame('8.8.8.8', $snapshot->host->ip);
        $this->assertSame('Google LLC', $snapshot->organization);
        $this->assertSame([53, 443], $snapshot->open_ports);
        $this->assertSame(38.00881, $snapshot->latitude);
        $this->assertSame(-122.11746, $snapshot->longitude);
    }

    public function test_visiting_the_same_host_twice_within_the_cooldown_reuses_the_snapshot(): void
    {
        Http::fake(['shodan.io/*' => Http::response($this->fixture(), 200)]);
        $service = $this->app->make(HostSnapshotService::class);

        $first = $service->getOrFetch('8.8.8.8');
        $second = $service->getOrFetch('8.8.8.8');

        $this->assertTrue($first->is($second));
        $this->assertDatabaseCount('host_snapshots', 1);
        Http::assertSentCount(1);
    }

    public function test_visiting_after_the_cooldown_expires_creates_a_second_snapshot(): void
    {
        Http::fake(['shodan.io/*' => Http::response($this->fixture(), 200)]);
        $service = $this->app->make(HostSnapshotService::class);

        $this->travelTo(now());
        $first = $service->getOrFetch('8.8.8.8');

        $this->travel((int) config('shodan.snapshot_cooldown_minutes') + 1)->minutes();
        $second = $service->getOrFetch('8.8.8.8');

        $this->assertFalse($first->is($second));
        $this->assertDatabaseCount('host_snapshots', 2);
        Http::assertSentCount(2);
    }

    private function fixture(): string
    {
        return file_get_contents(base_path('tests/Fixtures/shodan_host_8_8_8_8.html'));
    }
}
