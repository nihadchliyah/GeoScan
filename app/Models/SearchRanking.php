<?php

namespace App\Models;

use App\Enums\RankingType;
use Database\Factories\SearchRankingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchRanking extends Model
{
    /** @use HasFactory<SearchRankingFactory> */
    use HasFactory;

    protected $fillable = [
        'search_id',
        'type',
        'label',
        'count',
    ];

    protected function casts(): array
    {
        return [
            'type' => RankingType::class,
            'count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Search, $this>
     */
    public function search(): BelongsTo
    {
        return $this->belongsTo(Search::class);
    }
}
