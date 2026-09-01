<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    */
    'base_url' => env('SHODAN_BASE_URL', 'https://www.shodan.io'),

    /*
    |--------------------------------------------------------------------------
    | Crawl policy
    |--------------------------------------------------------------------------
    |
    | robots.txt (shodan.io/robots.txt) allows crawling everything except
    | /domain/*, with a Crawl-delay of 10 seconds. This app only ever
    | requests /search and /host/{ip}, so it stays within the allowed
    | paths — but the minimum delay below should still respect (or exceed)
    | that Crawl-delay directive.
    |
    */
    'user_agent' => env('SHODAN_USER_AGENT', 'GeoScanBot/1.0 (+educational TP; https://github.com/it-akademy)'),

    'min_delay_seconds' => (int) env('SHODAN_MIN_DELAY_SECONDS', 10),

    'request_timeout_seconds' => (int) env('SHODAN_REQUEST_TIMEOUT_SECONDS', 30),

    /*
    |--------------------------------------------------------------------------
    | CA bundle override
    |--------------------------------------------------------------------------
    |
    | Left null, curl uses whatever `curl.cainfo` is configured in php.ini
    | (the normal, secure default). Only set SHODAN_CA_BUNDLE locally if
    | that php.ini setting is broken on your machine (e.g. a stale path
    | left over from a moved Laragon install) and outbound HTTPS requests
    | fail with "cURL error 77: error setting certificate file".
    |
    */
    'ca_bundle' => env('SHODAN_CA_BUNDLE'),

    /*
    |--------------------------------------------------------------------------
    | Host snapshot cooldown
    |--------------------------------------------------------------------------
    |
    | A host page is only re-scraped if its most recent snapshot is older
    | than this many minutes. Within the cooldown window, the stored
    | snapshot is reused instead of hitting shodan.io again.
    |
    */
    'snapshot_cooldown_minutes' => (int) env('SHODAN_SNAPSHOT_COOLDOWN_MINUTES', 5),

];
