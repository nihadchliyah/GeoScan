<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_redirects_home_to_the_search_form(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/searches/create');
    }
}
