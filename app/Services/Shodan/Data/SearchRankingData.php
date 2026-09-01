<?php

namespace App\Services\Shodan\Data;

use App\Enums\RankingType;

final readonly class SearchRankingData
{
    public function __construct(
        public RankingType $type,
        public string $label,
        public int $count,
    ) {}
}
