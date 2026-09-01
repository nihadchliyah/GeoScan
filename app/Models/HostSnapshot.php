<?php

namespace App\Models;

use Database\Factories\HostSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostSnapshot extends Model
{
    /** @use HasFactory<HostSnapshotFactory> */
    use HasFactory;

    protected $fillable = [
        'host_id',
        'fetched_at',
        'shodan_last_update',
        'country',
        'city',
        'organization',
        'isp',
        'asn',
        'hostnames',
        'domains',
        'web_technologies',
        'open_ports',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'fetched_at' => 'datetime',
            'shodan_last_update' => 'datetime',
            'hostnames' => 'array',
            'domains' => 'array',
            'web_technologies' => 'array',
            'open_ports' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Host, $this>
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class);
    }
}
