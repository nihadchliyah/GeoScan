<?php

namespace App\Support;

/**
 * Reads the GPS coordinates embedded in a JPEG/TIFF's EXIF metadata, if
 * any. Nothing to do with Shodan or an IP address — this is standalone
 * file metadata that a camera/phone writes when location is enabled.
 */
class ExifGpsReader
{
    /**
     * @return array{0: float, 1: float}|null [latitude, longitude]
     */
    public static function fromPath(string $path): ?array
    {
        $exif = @exif_read_data($path);

        if ($exif === false || empty($exif['GPSLatitude']) || empty($exif['GPSLongitude'])) {
            return null;
        }

        $latitude = self::toDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef'] ?? 'N');
        $longitude = self::toDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'E');

        if ($latitude === null || $longitude === null) {
            return null;
        }

        return [$latitude, $longitude];
    }

    /**
     * @param  array<int, string>  $coordinate  [degrees, minutes, seconds] as EXIF rationals (e.g. "40/1")
     */
    public static function toDecimal(array $coordinate, string $ref): ?float
    {
        if (count($coordinate) !== 3) {
            return null;
        }

        [$degrees, $minutes, $seconds] = array_map(self::rationalToFloat(...), array_values($coordinate));

        $decimal = $degrees + $minutes / 60 + $seconds / 3600;

        return in_array(strtoupper($ref), ['S', 'W'], true) ? -$decimal : $decimal;
    }

    private static function rationalToFloat(string $rational): float
    {
        if (! str_contains($rational, '/')) {
            return (float) $rational;
        }

        [$numerator, $denominator] = explode('/', $rational, 2);
        $denominator = (float) $denominator;

        return $denominator !== 0.0 ? (float) $numerator / $denominator : 0.0;
    }
}
