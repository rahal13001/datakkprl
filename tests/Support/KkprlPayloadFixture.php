<?php

namespace Tests\Support;

final class KkprlPayloadFixture
{
    public static function value(string $field, mixed $fallback = 'Terisi'): mixed
    {
        return match ($field) {
            'shape_type' => 'point',
            'coordinates' => 'Titik 1: Latitude 6° 12\' 0" S (-6.2), Longitude 106° 48\' 0" E (106.8)',
            default => $fallback,
        };
    }
}
