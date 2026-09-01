<?php

namespace Database\Factories;

use App\Models\Search;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Search>
 */
class SearchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'query' => fake()->randomElement(['apache', 'nginx', 'country:"FR"', 'org:"Google LLC"']),
            'total_results' => fake()->numberBetween(100, 5_000_000),
            'searched_at' => fake()->dateTimeBetween('-1 month'),
        ];
    }
}
