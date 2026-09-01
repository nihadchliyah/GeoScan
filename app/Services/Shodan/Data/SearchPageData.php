<?php

namespace App\Services\Shodan\Data;

final readonly class SearchPageData
{
    /**
     * @param  array<int, SearchRankingData>  $rankings
     */
    public function __construct(
        public int $totalResults,
        public array $rankings,
    ) {}
}
