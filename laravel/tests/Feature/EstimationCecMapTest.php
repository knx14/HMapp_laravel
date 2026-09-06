<?php

use App\Models\Farm;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('cec map points json includes measurement_number', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->create();

    $uploadId = DB::table('uploads')->insertGetId([
        'farm_id' => $farm->id,
        'file_path' => 's3://test/cec-map-number.csv',
        'measurement_date' => '2026-09-06',
        'measurement_number' => 4,
        'status' => 'completed',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('analysis_results')->insert([
        'upload_id' => $uploadId,
        'sensor_info' => 'test',
        'latitude' => 35.1,
        'longitude' => 139.1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('estimation-results.cec', ['farm' => $farm->id, 'upload' => $uploadId]))
        ->assertOk()
        ->assertSee('"measurement_number":4', false);
});

test('cec map does not invent a number for manual points', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->create();

    $uploadId = DB::table('uploads')->insertGetId([
        'farm_id' => $farm->id,
        'file_path' => null,
        'measurement_date' => '2026-09-06',
        'measurement_number' => null,
        'measurement_parameters' => json_encode(['manual_entry' => true]),
        'status' => 'completed',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('analysis_results')->insert([
        'upload_id' => $uploadId,
        'sensor_info' => json_encode(['manual_entry' => true]),
        'latitude' => 35.1,
        'longitude' => 139.1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('estimation-results.cec', ['farm' => $farm->id, 'upload' => $uploadId]))
        ->assertOk()
        ->assertSee('"measurement_number":null', false);
});
