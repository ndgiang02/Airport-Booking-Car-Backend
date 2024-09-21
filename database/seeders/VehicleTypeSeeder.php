<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\VehicleType;

class VehicleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $vehicleTypes = [
            [
                'type' => 'sedan',
                'name' => 'Sedan',
                'starting_price' => 20000,
                'rate_per_km' => 8000,
                'seating_capacity' => 4,
                'image' => 'image_vehicle/sedan.png'
            ],
            [
                'type' => 'hatchback',
                'name' => 'Hatchback',
                'starting_price' => 15000,
                'rate_per_km' => 7000,
                'seating_capacity' => 4,
                'image' => 'image_vehicle/hatback.png'
            ],
            [
                'type' => 'suv',
                'name' => 'SUV',
                'starting_price' => 30000,
                'rate_per_km' => 10000,
                'seating_capacity' => 7,
                'image' => 'image_vehicle/suv.png'
            ],
            [
                'type' => 'mpv',
                'name' => 'MPV',
                'starting_price' => 28000,
                'rate_per_km' => 9500,
                'seating_capacity' => 7,
                'image' => 'image_vehicle/mpv.png'
            ],
            [
                'type' => 'van',
                'name' => 'Van',
                'starting_price' => 35000,
                'rate_per_km' => 12000,
                'seating_capacity' => 16,
                'image' => 'image_vehicle/van.png'
            ],
        ];

        foreach ($vehicleTypes as $type) {
            VehicleType::create($type);
        }
    }
}
