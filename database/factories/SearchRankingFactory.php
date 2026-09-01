<?php

namespace Database\Factories;

use App\Enums\RankingType;
use App\Models\Search;
use App\Models\SearchRanking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchRanking>
 */
class SearchRankingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'search_id' => Search::factory(),
            'type' => fake()->randomElement(RankingType::cases()),
            'label' => fake()->word(),
            'count' => fake()->numberBetween(1, 1_000_000),
        ];
    }
}
