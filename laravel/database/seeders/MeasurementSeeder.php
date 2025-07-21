<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Measurement;

class MeasurementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ダミーデータを複数挿入
        Measurement::create([
            'cognito_id' => 'user-abc-123',
            's3_file_path' => 's3://dummy-bucket/uploads/test_data_1.csv',
            'location_info' => 'Tokyo',
            'serial_no' => 'SN-001-A',
            'predicted_cec' => 35.12345,
        ]);

        Measurement::create([
            'cognito_id' => 'user-def-456',
            's3_file_path' => 's3://dummy-bucket/uploads/test_data_2.csv',
            'location_info' => 'Osaka',
            'serial_no' => 'SN-002-B',
            'predicted_cec' => 28.76543,
        ]);

        Measurement::create([
            'cognito_id' => 'user-xyz-789',
            's3_file_path' => 's3://dummy-bucket/uploads/test_data_3.csv',
            'location_info' => 'N/A', // JSONにない場合を想定
            'serial_no' => 'SN-003-C',
            'predicted_cec' => 42.00000,
        ]);
    }
}
