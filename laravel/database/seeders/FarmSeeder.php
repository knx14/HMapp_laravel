<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Farm;

class FarmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Farm::create([
            'app_user_id' => 1,
            'farm_name' => '離れの田んぼ（大）',
            'cultivation_method' => '水田',
            'crop_type' => '米',
            'boundary_polygon' => json_encode([
                'boundary_polygon' => [
                    [34.370349, 132.622356],
                    [34.370412, 132.622247],
                    [34.370546, 132.622425],
                    [34.370658, 132.622607],
                    [34.370577, 132.622690],
                    [34.370471, 132.622519]
                ]
            ])
        ]);

        Farm::create([
            'app_user_id' => 1,
            'farm_name' => '畑（大）',
            'cultivation_method' => '畑',
            'crop_type' => 'トマト、なすび',
            'boundary_polygon' => json_encode([
                ['lat' => 34.370694, 'lng' => 132.621662],
                ['lat' => 34.370856, 'lng' => 132.621766],
                ['lat' => 34.371046, 'lng' => 132.621919],
                ['lat' => 34.370995, 'lng' => 132.622082],
                ['lat' => 34.370810, 'lng' => 132.621970],
                ['lat' => 34.370647, 'lng' => 132.621861]
            ])
        ]);

        Farm::create([
            'app_user_id' => 1,
            'farm_name' => 'テスト圃場A',
            'cultivation_method' => '水田',
            'crop_type' => '米',
            'boundary_polygon' => json_encode([
                ['lat' => 35.6762, 'lng' => 139.6503],
                ['lat' => 35.6762, 'lng' => 139.6513],
                ['lat' => 35.6772, 'lng' => 139.6513],
                ['lat' => 35.6772, 'lng' => 139.6503]
            ])
        ]);

        Farm::create([
            'app_user_id' => 1,
            'farm_name' => 'テスト圃場B',
            'cultivation_method' => '畑',
            'crop_type' => 'トマト',
            'boundary_polygon' => json_encode([
                ['lat' => 35.6782, 'lng' => 139.6523],
                ['lat' => 35.6782, 'lng' => 139.6533],
                ['lat' => 35.6792, 'lng' => 139.6533],
                ['lat' => 35.6792, 'lng' => 139.6523]
            ])
        ]);
    }
}
