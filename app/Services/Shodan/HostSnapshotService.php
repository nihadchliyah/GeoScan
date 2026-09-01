<?php

namespace App\Services\Shodan;

use App\Models\Host;
use App\Models\HostSnapshot;

/**
 * Resolves the current snapshot for a host, scraping a fresh one only when
 * needed. This is the cooldown guard: if the most recent snapshot for the
 * IP is younger than `shodan.snapshot_cooldown_minutes`, it is reused
 * instead of hitting shodan.io again.
 */
class HostSnapshotService
{
    public function __construct(private readonly HostScraper $scraper) {}

    public function getOrFetch(string $ip): HostSnapshot
    {
        $host = Host::firstOrCreate(['ip' => $ip]);
        $latest = $host->latestSnapshot();

        if ($latest !== null && ! $this->isStale($latest)) {
            return $latest;
        }

        $data = $this->scraper->fetch($ip);

        return $host->snapshots()->create([
            'fetched_at' => now(),
            'shodan_last_update' => $data->lastSeenAt,
            'country' => $data->country,
            'city' => $data->city,
            'organization' => $data->organization,
            'isp' => $data->isp,
            'asn' => $data->asn,
            'hostnames' => $data->hostnames,
            'domains' => $data->domains,
            'web_technologies' => $data->webTechnologies,
            'open_ports' => $data->openPorts,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
        ]);
    }

    private function isStale(HostSnapshot $snapshot): bool
    {
        $cooldownMinutes = (int) config('shodan.snapshot_cooldown_minutes');

        return $snapshot->fetched_at->lte(now()->subMinutes($cooldownMinutes));
    }
}
