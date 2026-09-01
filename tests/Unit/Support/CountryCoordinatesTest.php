<?php

namespace Tests\Unit\Support;

use App\Support\CountryCoordinates;
use PHPUnit\Framework\TestCase;

class CountryCoordinatesTest extends TestCase
{
    public function test_it_finds_coordinates_for_a_known_country(): void
    {
        $coords = CountryCoordinates::lookup('France');

        $this->assertNotNull($coords);
        $this->assertEqualsWithDelta(46.6, $coords[0], 1.0);
        $this->assertEqualsWithDelta(2.2, $coords[1], 1.0);
    }

    public function test_it_returns_null_for_an_unknown_country_rather_than_guessing(): void
    {
        $this->assertNull(CountryCoordinates::lookup('Not A Real Country'));
    }
}
