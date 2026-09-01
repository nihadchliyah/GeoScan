<?php

namespace App\Services\Shodan;

use App\Services\Shodan\Data\HostPageData;
use Carbon\CarbonImmutable;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Fetches and parses a shodan.io/host/{ip} page.
 *
 * Parsing (parse()) is kept separate from fetching (fetch()) so tests can
 * exercise the DOM extraction against a saved HTML fixture without ever
 * making a network call.
 */
class HostScraper
{
    public function __construct(private readonly ShodanHttpClient $client) {}

    public function fetch(string $ip): HostPageData
    {
        $response = $this->client->get("/host/{$ip}");
        $response->throw();

        return $this->parse($response->body());
    }

    public function parse(string $html): HostPageData
    {
        $crawler = new Crawler($html);
        $title = $crawler->filter('.host-title');

        if ($title->count() === 0) {
            throw new RuntimeException('Shodan n\'a renvoyé aucune fiche pour cette IP (adresse invalide, non indexée, ou structure de page changée).');
        }

        [$longitude, $latitude] = $this->parseCoordinates($html);

        return new HostPageData(
            ip: trim($title->first()->text()),
            country: $this->fieldAfterLabel($crawler, 'Country'),
            city: $this->fieldAfterLabel($crawler, 'City'),
            organization: $this->fieldAfterLabel($crawler, 'Organization'),
            isp: $this->fieldAfterLabel($crawler, 'ISP'),
            asn: $this->fieldAfterLabel($crawler, 'ASN'),
            hostnames: $this->parseHostnames($crawler),
            domains: $this->parseDomains($crawler),
            webTechnologies: $this->parseWebTechnologies($crawler),
            openPorts: $this->parseOpenPorts($crawler),
            lastSeenAt: $this->parseLastSeen($crawler),
            latitude: $latitude,
            longitude: $longitude,
        );
    }

    private function fieldAfterLabel(Crawler $crawler, string $label): ?string
    {
        $node = $crawler->filterXPath($this->labelValueXPath($label));

        if ($node->count() === 0) {
            return null;
        }

        $text = trim($node->first()->text());

        return $text !== '' ? $text : null;
    }

    /**
     * @return array<int, string>
     */
    private function parseHostnames(Crawler $crawler): array
    {
        $node = $crawler->filterXPath($this->labelValueXPath('Hostnames'));

        if ($node->count() === 0) {
            return [];
        }

        $lines = preg_split('/<br\s*\/?>/i', $node->first()->html()) ?: [];

        return collect($lines)
            ->map(fn (string $line) => trim(strip_tags($line)))
            ->filter(fn (string $line) => $line !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function parseDomains(Crawler $crawler): array
    {
        return $crawler->filter('.domains a')
            ->each(fn (Crawler $a) => trim($a->text()));
    }

    /**
     * @return array<int, string>
     */
    private function parseWebTechnologies(Crawler $crawler): array
    {
        return $crawler->filter('#http-components .technology-name')
            ->each(fn (Crawler $span) => trim($span->text()));
    }

    /**
     * @return array<int, int>
     */
    private function parseOpenPorts(Crawler $crawler): array
    {
        $ports = $crawler->filter('#ports a')
            ->each(fn (Crawler $a) => (int) trim($a->text()));

        return array_values(array_unique($ports));
    }

    private function parseLastSeen(Crawler $crawler): ?CarbonImmutable
    {
        $node = $crawler->filter('.top-info span');

        if ($node->count() === 0) {
            return null;
        }

        if (! preg_match('/(\d{4}-\d{2}-\d{2})/', $node->first()->text(), $matches)) {
            return null;
        }

        return CarbonImmutable::parse($matches[1]);
    }

    /**
     * The host page embeds a Mapbox init with the host's own coordinates
     * directly in an inline <script> — e.g.
     * `new mapboxgl.Map({..., center: [-122.11746, 38.00881], ...})`.
     * That's the only place shodan.io exposes lat/lng for a host on the
     * public page, so we pull it out with a regex rather than the DOM.
     *
     * @return array{0: ?float, 1: ?float} [longitude, latitude]
     */
    private function parseCoordinates(string $html): array
    {
        if (! preg_match('/center:\s*\[\s*(-?[\d.]+)\s*,\s*(-?[\d.]+)\s*\]/', $html, $matches)) {
            return [null, null];
        }

        return [(float) $matches[1], (float) $matches[2]];
    }

    private function labelValueXPath(string $label): string
    {
        return sprintf(
            '//div[contains(concat(" ", normalize-space(@class), " "), " grid-table ")]//label[normalize-space(text())="%s"]/following-sibling::div[1]',
            $label
        );
    }
}
