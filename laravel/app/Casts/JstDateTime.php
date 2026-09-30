<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * 日本時間の壁時計時刻をそのまま DATETIME に保存する。アプリのタイムゾーン（UTC）には変換しない。
 */
class JstDateTime implements CastsAttributes
{
    public const TIMEZONE = 'Asia/Tokyo';

    private const FORMAT = 'Y-m-d H:i:s';

    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        return CarbonImmutable::createFromFormat(self::FORMAT, substr((string) $value, 0, 19), self::TIMEZONE);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = $value instanceof DateTimeInterface
            ? CarbonImmutable::instance($value)
            : CarbonImmutable::parse((string) $value, self::TIMEZONE);

        return $date->setTimezone(self::TIMEZONE)->format(self::FORMAT);
    }
}
