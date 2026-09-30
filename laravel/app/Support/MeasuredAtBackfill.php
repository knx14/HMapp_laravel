<?php

namespace App\Support;

use App\Casts\JstDateTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * 既存の測定に測定日時（日本時間）を入れる。
 *
 * 1. モバイルが保存した measurement_parameters.timestamp（オフセット無しは日本時間とみなす）
 * 2. 無ければ created_at（UTC）を日本時間にしたもの
 *
 * どちらも measurement_date と日付が一致するときだけ採用する。手動入力は時刻が無いので NULL のまま。
 */
class MeasuredAtBackfill
{
    public function run(): void
    {
        DB::table('uploads')
            ->whereNull('measured_at')
            ->whereNotNull('measurement_date')
            ->orderBy('id')
            ->select(['id', 'measurement_date', 'measurement_parameters', 'created_at'])
            ->chunkById(500, function ($uploads): void {
                foreach ($uploads as $upload) {
                    $measuredAt = self::resolve($upload->measurement_date, $upload->measurement_parameters, $upload->created_at);
                    if ($measuredAt === null) {
                        continue;
                    }

                    DB::table('uploads')->where('id', $upload->id)->update(['measured_at' => $measuredAt]);
                }
            });
    }

    public static function resolve(?string $measurementDate, ?string $parametersJson, ?string $createdAt): ?string
    {
        $parameters = json_decode((string) $parametersJson, true);
        if (!is_array($parameters) || ($parameters['manual_entry'] ?? false) === true) {
            return null;
        }

        $date = substr((string) $measurementDate, 0, 10);
        $candidates = [
            self::fromTimestamp($parameters['timestamp'] ?? null),
            $createdAt ? self::parse($createdAt, 'UTC') : null,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== null && $candidate->format('Y-m-d') === $date) {
                return $candidate->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    private static function fromTimestamp(mixed $timestamp): ?CarbonImmutable
    {
        if (!is_string($timestamp) || strlen($timestamp) < 16) {
            return null;
        }

        return self::parse($timestamp, JstDateTime::TIMEZONE);
    }

    private static function parse(string $value, string $timezone): ?CarbonImmutable
    {
        try {
            return CarbonImmutable::parse($value, $timezone)->setTimezone(JstDateTime::TIMEZONE);
        } catch (Throwable) {
            return null;
        }
    }
}
