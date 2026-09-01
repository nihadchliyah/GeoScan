<?php

namespace App\Services\Shodan;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Thin, policy-aware wrapper around Guzzle (via Laravel's HTTP client) for
 * talking to the public, non-API pages of shodan.io.
 *
 * Crawl policy (see shodan.io/robots.txt):
 * - Allowed: everything except /domain/*. This client only ever calls
 *   /search and /host/{ip}, both of which are allowed.
 * - robots.txt sets `Crawl-delay: 10`. `shodan.min_delay_seconds` should
 *   be set to at least that value; it is enforced here between any two
 *   requests, regardless of which HTTP request triggered them.
 * - Every request is sent with an identifiable User-Agent
 *   (`shodan.user_agent`) rather than a browser-spoofing one.
 *
 * When the optional login extension is enabled (see ShodanSession), an
 * authenticated cookie jar is attached to every request too.
 */
class ShodanHttpClient
{
    private const LAST_REQUEST_CACHE_KEY = 'shodan:last_request_at';

    public function __construct(private readonly ShodanSession $session) {}

    public function get(string $path, array $query = []): Response
    {
        $this->waitForCrawlDelay();

        $request = Http::withHeaders([
            'User-Agent' => config('shodan.user_agent'),
        ])->timeout(config('shodan.request_timeout_seconds'));

        if ($caBundle = config('shodan.ca_bundle')) {
            $request = $request->withOptions(['verify' => $caBundle]);
        }

        if ($jar = $this->session->cookieJar()) {
            $request = $request->withOptions(['cookies' => $jar]);
        }

        $response = $request->get(rtrim(config('shodan.base_url'), '/').$path, $query);

        Cache::put(self::LAST_REQUEST_CACHE_KEY, microtime(true), now()->addMinutes(10));

        return $response;
    }

    /**
     * Block until at least `shodan.min_delay_seconds` have passed since
     * the previous request made by any part of the app.
     */
    private function waitForCrawlDelay(): void
    {
        $minDelaySeconds = (float) config('shodan.min_delay_seconds');
        $lastRequestAt = Cache::get(self::LAST_REQUEST_CACHE_KEY);

        if ($lastRequestAt === null) {
            return;
        }

        $remainingSeconds = $minDelaySeconds - (microtime(true) - $lastRequestAt);

        if ($remainingSeconds > 0) {
            usleep((int) round($remainingSeconds * 1_000_000));
        }
    }
}
