<?php

namespace App\Models;

use Database\Factories\HostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Host extends Model
{
    /** @use HasFactory<HostFactory> */
    use HasFactory;

    protected $fillable = [
        'ip',
    ];

    /**
     * @return HasMany<HostSnapshot, $this>
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(HostSnapshot::class);
    }

    public function latestSnapshot(): ?HostSnapshot
    {
        return $this->snapshots()->latest('fetched_at')->first();
    }
}
