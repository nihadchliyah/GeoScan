<?php

namespace Tests\Feature;

use App\Services\Shodan\ShodanSession;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ShodanSessionTest extends TestCase
{
    public function test_it_returns_null_and_makes_no_request_when_the_login_extension_is_disabled(): void
    {
        config(['shodan.login.enabled' => false]);
        Http::fake();

        $jar = $this->app->make(ShodanSession::class)->cookieJar();

        $this->assertNull($jar);
        Http::assertNothingSent();
    }

    public function test_it_throws_when_enabled_without_credentials(): void
    {
        config(['shodan.login.enabled' => true, 'shodan.login.email' => null, 'shodan.login.password' => null]);

        $this->expectException(RuntimeException::class);

        $this->app->make(ShodanSession::class)->cookieJar();
    }

    public function test_a_successful_login_returns_a_cookie_jar_and_caches_it(): void
    {
        config([
            'shodan.login.enabled' => true,
            'shodan.login.email' => 'demo@example.com',
            'shodan.login.password' => 'secret',
        ]);

        Http::fake([
            'account.shodan.io/login' => Http::sequence()
                ->push($this->loginFormHtml(), 200)
                ->push('', 302),
            'www.shodan.io*' => Http::response($this->loggedInHomepageHtml(), 200),
        ]);

        $jar = $this->app->make(ShodanSession::class)->cookieJar();

        $this->assertNotNull($jar);

        // A second call must reuse the cached session — no extra HTTP calls.
        Http::fake([
            'account.shodan.io/login' => Http::response('should not be called', 200),
            'www.shodan.io*' => Http::response('should not be called', 200),
        ]);
        $this->app->make(ShodanSession::class)->cookieJar();
        Http::assertNothingSent();
    }

    public function test_it_throws_when_shodan_rejects_the_credentials(): void
    {
        config([
            'shodan.login.enabled' => true,
            'shodan.login.email' => 'demo@example.com',
            'shodan.login.password' => 'wrong',
        ]);

        Http::fake([
            'account.shodan.io/login' => Http::sequence()
                ->push($this->loginFormHtml(), 200)
                ->push($this->loginFormHtml(), 200), // still shows the form: login failed
            'www.shodan.io*' => Http::response($this->anonymousHomepageHtml(), 200),
        ]);

        $this->expectException(RuntimeException::class);

        $this->app->make(ShodanSession::class)->cookieJar();
    }

    private function loginFormHtml(): string
    {
        return '<html><body><form method="post" action="/login">'
            .'<input type="hidden" name="csrf_token" value="fake-csrf-token"/>'
            .'</form></body></html>';
    }

    private function loggedInHomepageHtml(): string
    {
        return '<html><body><nav><a href="/account">demo@example.com</a></nav></body></html>';
    }

    private function anonymousHomepageHtml(): string
    {
        return '<html><body><nav><a href="/dashboard" class="highlight-success">Login</a></nav></body></html>';
    }
}
