<?php

namespace App\Enums;

enum RankingType: string
{
    case Country = 'country';
    case Port = 'port';
    case Organization = 'organization';
    case Product = 'product';
    case Os = 'os';

    /**
     * Human-readable label, in the order Shodan renders these facets.
     *
     * @return array<value-of<self>, string>
     */
    public static function labels(): array
    {
        return [
            self::Country->value => 'Top Countries',
            self::Port->value => 'Top Ports',
            self::Organization->value => 'Top Organizations',
            self::Product->value => 'Top Products',
            self::Os->value => 'Top Operating Systems',
        ];
    }
}
