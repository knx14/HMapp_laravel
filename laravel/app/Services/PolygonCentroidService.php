<?php

namespace App\Services;

class PolygonCentroidService
{
    /**
     * 圃場の boundary_polygon（正規化済み配列）から単純重心を算出する。
     *
     * @param  array<int, array{lat: float, lng: float}>  $normalizedPolygon
     * @return array{latitude: float, longitude: float}
     */
    public function centroidOf(array $normalizedPolygon): array
    {
        $count = count($normalizedPolygon);
        $latSum = array_sum(array_column($normalizedPolygon, 'lat'));
        $lngSum = array_sum(array_column($normalizedPolygon, 'lng'));

        return [
            'latitude' => round($latSum / $count, 7),
            'longitude' => round($lngSum / $count, 7),
        ];
    }
}
