<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Any HTTP call not covered by Http::fake() fails fast instead of
        // silently hitting the real network (and possibly hanging).
        Http::preventStrayRequests();
    }
}
