<?php

namespace App\Support;

/**
 * Approximate centroid [latitude, longitude] for countries, keyed by the
 * English name Shodan's search page renders (e.g. "United States",
 * "South Korea"). Good enough to place a bubble on a world map — not
 * survey-grade precision.
 *
 * Coverage is best-effort: it lists the countries that realistically show
 * up in a Shodan "Top Countries" list (the ones with meaningful internet
 * infrastructure), not every country in the world. lookup() returns null
 * for anything missing rather than guessing.
 */
class CountryCoordinates
{
    /**
     * @var array<string, array{0: float, 1: float}>
     */
    private static array $coordinates = [
        'United States' => [39.8, -98.6],
        'Canada' => [56.1, -106.3],
        'Mexico' => [23.6, -102.5],
        'Brazil' => [-14.2, -51.9],
        'Argentina' => [-38.4, -63.6],
        'Chile' => [-35.7, -71.5],
        'Colombia' => [4.6, -74.3],
        'Peru' => [-9.2, -75.0],
        'United Kingdom' => [54.0, -2.9],
        'Ireland' => [53.4, -8.2],
        'France' => [46.6, 2.2],
        'Germany' => [51.2, 10.5],
        'Netherlands' => [52.2, 5.3],
        'Belgium' => [50.5, 4.5],
        'Switzerland' => [46.8, 8.2],
        'Austria' => [47.5, 14.6],
        'Spain' => [40.5, -3.7],
        'Portugal' => [39.4, -8.2],
        'Italy' => [41.9, 12.6],
        'Poland' => [51.9, 19.1],
        'Czechia' => [49.8, 15.5],
        'Czech Republic' => [49.8, 15.5],
        'Slovakia' => [48.7, 19.7],
        'Hungary' => [47.2, 19.5],
        'Romania' => [45.9, 24.9],
        'Bulgaria' => [42.7, 25.5],
        'Greece' => [39.1, 21.8],
        'Sweden' => [60.1, 18.6],
        'Norway' => [60.5, 8.5],
        'Finland' => [61.9, 25.7],
        'Denmark' => [56.3, 9.5],
        'Iceland' => [64.9, -19.0],
        'Ukraine' => [48.4, 31.2],
        'Russia' => [61.5, 105.3],
        'Turkey' => [38.9, 35.2],
        'Israel' => [31.0, 34.8],
        'Saudi Arabia' => [23.9, 45.1],
        'United Arab Emirates' => [23.4, 53.8],
        'Iran' => [32.4, 53.7],
        'Iraq' => [33.2, 43.7],
        'Egypt' => [26.8, 30.8],
        'South Africa' => [-30.6, 22.9],
        'Nigeria' => [9.1, 8.7],
        'Kenya' => [-0.0, 37.9],
        'Morocco' => [31.8, -7.1],
        'India' => [20.6, 79.0],
        'Pakistan' => [30.4, 69.3],
        'Bangladesh' => [23.7, 90.4],
        'China' => [35.9, 104.2],
        'Hong Kong' => [22.3, 114.2],
        'Taiwan' => [23.7, 121.0],
        'Japan' => [36.2, 138.3],
        'South Korea' => [35.9, 127.8],
        'North Korea' => [40.3, 127.5],
        'Vietnam' => [14.1, 108.3],
        'Thailand' => [15.9, 100.9],
        'Malaysia' => [4.2, 101.9],
        'Singapore' => [1.35, 103.8],
        'Indonesia' => [-0.8, 113.9],
        'Philippines' => [12.9, 121.8],
        'Australia' => [-25.3, 133.8],
        'New Zealand' => [-40.9, 174.9],
        'Kazakhstan' => [48.0, 66.9],
        'Uzbekistan' => [41.4, 64.6],
        'Georgia' => [42.3, 43.4],
        'Armenia' => [40.1, 45.0],
        'Azerbaijan' => [40.1, 47.6],
        'Moldova' => [47.4, 28.4],
        'Belarus' => [53.7, 27.9],
        'Lithuania' => [55.2, 23.9],
        'Latvia' => [56.9, 24.6],
        'Estonia' => [58.6, 25.0],
        'Croatia' => [45.1, 15.2],
        'Serbia' => [44.0, 21.0],
        'Slovenia' => [46.1, 14.8],
        'Bosnia and Herzegovina' => [43.9, 17.7],
        'Albania' => [41.2, 20.2],
        'Cyprus' => [35.1, 33.4],
        'Malta' => [35.9, 14.4],
        'Luxembourg' => [49.8, 6.1],
        'Venezuela' => [6.4, -66.6],
        'Ecuador' => [-1.8, -78.2],
        'Bolivia' => [-16.3, -63.6],
        'Paraguay' => [-23.4, -58.4],
        'Uruguay' => [-32.5, -55.8],
        'Costa Rica' => [9.7, -83.8],
        'Panama' => [8.5, -80.8],
        'Cuba' => [21.5, -77.8],
        'Dominican Republic' => [18.7, -70.2],
        'Guatemala' => [15.8, -90.2],
        'Honduras' => [15.2, -86.2],
    ];

    /**
     * @return array{0: float, 1: float}|null
     */
    public static function lookup(string $countryName): ?array
    {
        return self::$coordinates[$countryName] ?? null;
    }
}
