<?php

namespace App\Services\Shodan\Data;

final readonly class SearchPageData
{
    /**
     * @param  array<int, SearchRankingData>  $rankings
     * @param  array<int, string>  $resultIps  IPs of the individual results listed on this page, in display order.
     */
    public function __construct(
        public int $totalResults,
        public array $rankings,
        public array $resultIps,
    ) {}
}
