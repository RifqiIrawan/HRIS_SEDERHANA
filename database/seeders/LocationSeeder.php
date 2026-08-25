<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * Spec §12 — sample parking locations. Coordinates are real Jakarta landmarks
 * so the Leaflet map has something recognisable to show during a demo.
 */
class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            ['LOC001', 'Parkir Mall A', 'Jl. M.H. Thamrin No. 1, Jakarta Pusat', -6.1944000, 106.8229000],
            ['LOC002', 'Parkir Stasiun B', 'Jl. Stasiun Gambir, Jakarta Pusat', -6.1766000, 106.8306000],
            ['LOC003', 'Parkir Rumah Sakit C', 'Jl. Diponegoro No. 71, Jakarta Pusat', -6.1980000, 106.8380000],
        ];

        foreach ($locations as [$code, $name, $address, $lat, $lng]) {
            Location::updateOrCreate(
                ['location_code' => $code],
                [
                    'location_name' => $name,
                    'address' => $address,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'radius_meter' => config('parkops.default_radius_meter'),
                    'gps_accuracy_limit' => config('parkops.default_gps_accuracy_limit'),
                    'status' => 'ACTIVE',
                ],
            );
        }
    }
}
