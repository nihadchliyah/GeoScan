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

    public function test_it_only_shows_hosts_matching_the_organization_filter(): void
    {
        $google = Host::factory()->create();
        HostSnapshot::factory()->for($google)->create(['organization' => 'Google LLC', 'latitude' => 37.4, 'longitude' => -122.1]);

        $other = Host::factory()->create();
        HostSnapshot::factory()->for($other)->create(['organization' => 'OVH SAS', 'latitude' => 48.85, 'longitude' => 2.35]);

        $response = $this->get(route('map.index', ['organization' => 'Google']));

        $response->assertOk();
        $response->assertSeeText($google->ip);
        $response->assertDontSeeText($other->ip);
    }

    public function test_it_only_shows_hosts_matching_the_isp_filter(): void
    {
        $orange = Host::factory()->create();
        HostSnapshot::factory()->for($orange)->create(['isp' => 'Orange', 'latitude' => 48.85, 'longitude' => 2.35]);

        $other = Host::factory()->create();
        HostSnapshot::factory()->for($other)->create(['isp' => 'Free Pro SAS', 'latitude' => 52.52, 'longitude' => 13.40]);

        $response = $this->get(route('map.index', ['isp' => 'Orange']));

        $response->assertOk();
        $response->assertSeeText($orange->ip);
        $response->assertDontSeeText($other->ip);
    }

    public function test_it_only_shows_hosts_matching_the_asn_filter(): void
    {
        $matching = Host::factory()->create();
        HostSnapshot::factory()->for($matching)->create(['asn' => 'AS15169', 'latitude' => 37.4, 'longitude' => -122.1]);

        $other = Host::factory()->create();
        HostSnapshot::factory()->for($other)->create(['asn' => 'AS16276', 'latitude' => 48.85, 'longitude' => 2.35]);

        $response = $this->get(route('map.index', ['asn' => 'AS15169']));

        $response->assertOk();
        $response->assertSeeText($matching->ip);
        $response->assertDontSeeText($other->ip);
    }

    public function test_it_only_shows_hosts_matching_the_product_filter(): void
    {
        $nginx = Host::factory()->create();
        HostSnapshot::factory()->for($nginx)->create(['web_technologies' => ['nginx', 'PHP'], 'latitude' => 48.85, 'longitude' => 2.35]);

        $apache = Host::factory()->create();
        HostSnapshot::factory()->for($apache)->create(['web_technologies' => ['Apache HTTP Server'], 'latitude' => 52.52, 'longitude' => 13.40]);

        $response = $this->get(route('map.index', ['product' => 'nginx']));

        $response->assertOk();
        $response->assertSeeText($nginx->ip);
        $response->assertDontSeeText($apache->ip);
    }

    public function test_it_only_shows_hosts_matching_the_hostname_filter(): void
    {
        $matching = Host::factory()->create();
        HostSnapshot::factory()->for($matching)->create(['hostnames' => ['mail.example.com'], 'domains' => ['example.com'], 'latitude' => 48.85, 'longitude' => 2.35]);

        $other = Host::factory()->create();
        HostSnapshot::factory()->for($other)->create(['hostnames' => ['host.other.net'], 'domains' => ['other.net'], 'latitude' => 52.52, 'longitude' => 13.40]);

        $response = $this->get(route('map.index', ['hostname' => 'example.com']));

        $response->assertOk();
        $response->assertSeeText($matching->ip);
        $response->assertDontSeeText($other->ip);
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
