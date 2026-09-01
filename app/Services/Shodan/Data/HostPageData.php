<?php

namespace App\Services\Shodan\Data;

use Carbon\CarbonImmutable;

final readonly class HostPageData
{
    /**
     * @param  array<int, string>  $hostnames
     * @param  array<int, string>  $domains
     * @param  array<int, string>  $webTechnologies
     * @param  array<int, int>  $openPorts
     */
    public function __construct(
        public string $ip,
        public ?string $country,
        public ?string $city,
        public ?string $organization,
        public ?string $isp,
        public ?string $asn,
        public array $hostnames,
        public array $domains,
        public array $webTechnologies,
        public array $openPorts,
        public ?CarbonImmutable $lastSeenAt,
        public ?float $latitude,
        public ?float $longitude,
    ) {}
}
