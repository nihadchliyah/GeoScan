<?php

namespace App\Models;

use Database\Factories\SearchFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Search extends Model
{
    /** @use HasFactory<SearchFactory> */
    use HasFactory;

    protected $fillable = [
        'query',
        'total_results',
        'searched_at',
    ];

    protected function casts(): array
    {
        return [
            'total_results' => 'integer',
            'searched_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<SearchRanking, $this>
     */
    public function rankings(): HasMany
    {
        return $this->hasMany(SearchRanking::class);
    }

    /**
     * Rankings for this search, grouped by type in Shodan's display order.
     *
     * @return Collection<string, Collection<int, SearchRanking>>
     */
    protected function rankingsByType(): Attribute
    {
        return Attribute::get(fn () => $this->rankings->groupBy(fn (SearchRanking $ranking) => $ranking->type->value));
    }

    /**
     * The individual hosts listed among this search's results, each with
     * the exact GPS coordinates from its own host page — in the order
     * Shodan displayed them.
     *
     * @return BelongsToMany<HostSnapshot, $this>
     */
    public function hostSnapshots(): BelongsToMany
    {
        return $this->belongsToMany(HostSnapshot::class, 'search_results')
            ->withPivot('position')
            ->orderByPivot('position');
    }
}
