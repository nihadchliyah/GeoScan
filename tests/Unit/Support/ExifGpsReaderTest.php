<?php

namespace Tests\Unit\Support;

use App\Support\ExifGpsReader;
use PHPUnit\Framework\TestCase;

class ExifGpsReaderTest extends TestCase
{
    public function test_it_converts_a_north_east_dms_coordinate_to_decimal(): void
    {
        // The Eiffel Tower: 48°51'29.6"N 2°17'40.2"E
        $latitude = ExifGpsReader::toDecimal(['48/1', '51/1', '296/10'], 'N');
        $longitude = ExifGpsReader::toDecimal(['2/1', '17/1', '402/10'], 'E');

        $this->assertEqualsWithDelta(48.8582, $latitude, 0.0001);
        $this->assertEqualsWithDelta(2.29450, $longitude, 0.0001);
    }

    public function test_south_and_west_references_produce_negative_decimals(): void
    {
        $latitude = ExifGpsReader::toDecimal(['33/1', '52/1', '0/1'], 'S');
        $longitude = ExifGpsReader::toDecimal(['151/1', '12/1', '0/1'], 'W');

        $this->assertLessThan(0, $latitude);
        $this->assertLessThan(0, $longitude);
    }

    public function test_a_malformed_coordinate_array_returns_null(): void
    {
        $this->assertNull(ExifGpsReader::toDecimal(['1/1', '2/1'], 'N'));
    }

    public function test_a_file_without_exif_data_returns_no_coordinates(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'geoscan-test-');
        file_put_contents($path, 'not an image');

        $this->assertNull(ExifGpsReader::fromPath($path));

        unlink($path);
    }
}
