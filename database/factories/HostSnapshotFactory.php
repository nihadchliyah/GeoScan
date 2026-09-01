<?php

namespace Database\Factories;

use App\Models\Host;
use App\Models\HostSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HostSnapshot>
 */
class HostSnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'host_id' => Host::factory(),
            'fetched_at' => fake()->dateTimeBetween('-1 month'),
            'shodan_last_update' => fake()->dateTimeBetween('-1 month'),
            'country' => fake()->country(),
            'city' => fake()->city(),
            'organization' => fake()->company(),
            'isp' => fake()->company(),
            'asn' => 'AS'.fake()->numberBetween(1000, 99999),
            'hostnames' => [fake()->domainName()],
            'domains' => [fake()->domainName()],
            'web_technologies' => fake()->randomElements(['nginx', 'Apache HTTP Server', 'HSTS', 'PHP'], 2),
            'open_ports' => fake()->randomElements([22, 80, 443, 8080], 2),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ];
    }
}
