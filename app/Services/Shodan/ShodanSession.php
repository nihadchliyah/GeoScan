<?php

namespace App\Services\Shodan;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Logs into a real Shodan account (on account.shodan.io, a different host
 * from www.shodan.io) and keeps the resulting session cookies cached so
 * ShodanHttpClient can attach them to every request. This is what unlocks
 * sections hidden for anonymous visitors — search filters (`country:`,
 * `org:`, ...), extra result pages, extra host-page sections.
 *
 * Opt-in extension, not part of the base TP: does nothing unless
 * `shodan.login.enabled` is true and `shodan.login.email`/`password` are
 * set. The login form's shape (fields, CSRF token) was confirmed against
 * the real page; the POST itself was NOT tested end-to-end against a real
 * account while building this — verify it works with your own credentials
 * and adjust `looksLoggedIn()` below if Shodan ever rejects a correct
 * login here.
 */
class ShodanSession
{
    private const LOGIN_URL = 'https://account.shodan.io/login';

    private const CACHE_KEY = 'shodan:session_cookies';

    private const CACHE_TTL_HOURS = 6;

    /**
     * Returns the authenticated cookie jar, logging in (and caching the
     * result) if needed. Returns null when the login extension is off.
     */
    public function cookieJar(): ?CookieJar
    {
        if (! config('shodan.login.enabled')) {
            return null;
        }

        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return new CookieJar(false, $cached);
        }

        $jar = $this->login();

        Cache::put(self::CACHE_KEY, $jar->toArray(), now()->addHours(self::CACHE_TTL_HOURS));

        return $jar;
    }

    /**
     * Forces the next cookieJar() call to log in again, e.g. after a
     * request comes back looking like it lost its authenticated session.
     */
    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function login(): CookieJar
    {
        $email = config('shodan.login.email');
        $password = config('shodan.login.password');

        if (! $email || ! $password) {
            throw new RuntimeException('SHODAN_LOGIN_ENABLED est activé mais SHODAN_EMAIL / SHODAN_PASSWORD sont vides dans le .env.');
        }

        $jar = new CookieJar;
        $headers = ['User-Agent' => config('shodan.user_agent')];

        $loginPage = Http::withHeaders($headers)->withOptions(['cookies' => $jar])->get(self::LOGIN_URL);
        $loginPage->throw();

        $response = Http::withHeaders($headers)
            ->withOptions(['cookies' => $jar])
            ->asForm()
            ->post(self::LOGIN_URL, [
                'username' => $email,
                'password' => $password,
                'grant_type' => 'password',
                'continue' => config('shodan.base_url'),
                'csrf_token' => $this->extractCsrfToken($loginPage->body()),
            ]);
        $response->throw();

        if (! $this->looksLoggedIn($jar, $headers)) {
            throw new RuntimeException('Connexion à Shodan échouée : identifiants incorrects, ou Shodan demande une vérification (captcha/2FA) que ce client ne sait pas résoudre.');
        }

        return $jar;
    }

    private function extractCsrfToken(string $html): string
    {
        $input = (new Crawler($html))->filter('input[name=csrf_token]');

        if ($input->count() === 0) {
            throw new RuntimeException('Jeton CSRF introuvable sur la page de connexion Shodan (structure de page changée).');
        }

        return (string) $input->first()->attr('value');
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function looksLoggedIn(CookieJar $jar, array $headers): bool
    {
        $homepage = Http::withHeaders($headers)
            ->withOptions(['cookies' => $jar])
            ->get(config('shodan.base_url'));

        // Anonymous visitors get a plain "Login" link in the top nav;
        // an authenticated session replaces it with account info.
        return $homepage->successful() && ! str_contains($homepage->body(), 'highlight-success">Login<');
    }
}
