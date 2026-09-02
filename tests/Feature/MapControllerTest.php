<?php

namespace Tests\Feature;

use App\Models\Host;
use App\Models\HostSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_shows_hosts_matching_the_country_filter(): void
    {
        $french = Host::factory()->create();
        HostSnapshot::factory()->for($french)->create(['country' => 'France', 'latitude' => 48.85, 'longitude' => 2.35]);

        $german = Host::factory()->create();
        HostSnapshot::factory()->for($german)->create(['country' => 'Germany', 'latitude' => 52.52, 'longitude' => 13.40]);

        $response = $this->get(route('map.index', ['country' => 'France']));

        $response->assertOk();
        $response->assertSeeText($french->ip);
        $response->assertDontSeeText($german->ip);
    }

    public function test_it_only_shows_hosts_with_the_filtered_port_open(): void
    {
        $withPort80 = Host::factory()->create();
        HostSnapshot::factory()->for($withPort80)->create(['open_ports' => [80, 443], 'latitude' => 48.85, 'longitude' => 2.35]);

        $withoutPort80 = Host::factory()->create();
        HostSnapshot::factory()->for($withoutPort80)->create(['open_ports' => [22], 'latitude' => 52.52, 'longitude' => 13.40]);

        $response = $this->get(route('map.index', ['port' => 80]));

        $response->assertOk();
        $response->assertSeeText($withPort80->ip);
        $response->assertDontSeeText($withoutPort80->ip);
    }

    public function test_it_only_shows_the_latest_snapshot_of_each_host(): void
    {
        $host = Host::factory()->create();
        HostSnapshot::factory()->for($host)->create([
            'country' => 'Germany',
            'fetched_at' => now()->subDay(),
            'latitude' => 52.52,
            'longitude' => 13.40,
        ]);
        HostSnapshot::factory()->for($host)->create([
            'country' => 'France',
            'fetched_at' => now(),
            'latitude' => 48.85,
            'longitude' => 2.35,
        ]);

        $matchingOldCountry = $this->get(route('map.index', ['country' => 'Germany']));
        $matchingNewCountry = $this->get(route('map.index', ['country' => 'France']));

        $matchingOldCountry->assertDontSeeText($host->ip);
        $matchingNewCountry->assertSeeText($host->ip);
    }

    public function test_a_host_without_coordinates_is_left_off_the_map(): void
    {
        $host = Host::factory()->create();
        HostSnapshot::factory()->for($host)->create(['latitude' => null, 'longitude' => null]);

        $response = $this->get(route('map.index'));

        $response->assertOk();
        $response->assertDontSeeText($host->ip);
    }
}
