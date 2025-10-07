<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Farm;
use Illuminate\Support\Facades\DB;

class FixBoundaryPolygonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存のデータを確認して修正
        $farms = Farm::all();
        
        foreach ($farms as $farm) {
            $boundaryData = $farm->boundary_polygon;
            
            // データが既に正しい形式かチェック
            if (is_array($boundaryData) && count($boundaryData) > 0) {
                $firstItem = $boundaryData[0];
                
                // 既に正しい形式（lat/lng）の場合はスキップ
                if (isset($firstItem['lat']) && isset($firstItem['lng'])) {
                    continue;
                }
                
                // 配列形式の場合は変換
                if (is_array($firstItem) && count($firstItem) >= 2) {
                    $convertedData = [];
                    foreach ($boundaryData as $coord) {
                        if (is_array($coord) && count($coord) >= 2) {
                            $convertedData[] = [
                                'lat' => (float)$coord[0],
                                'lng' => (float)$coord[1]
                            ];
                        }
                    }
                    
                    // データベースを更新
                    DB::table('farms')
                        ->where('id', $farm->id)
                        ->update(['boundary_polygon' => json_encode($convertedData)]);
                        
                    echo "Farm ID {$farm->id} boundary data updated.\n";
                }
            }
        }
        
        echo "Boundary polygon data fix completed.\n";
    }
}
