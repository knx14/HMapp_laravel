<?php

namespace App\Support;

class SoilParameterUnits
{
    /**
     * 推論・手動入力・画面表示で共通の17パラメータ単位マップ。
     *
     * @var array<string, string>
     */
    private const UNITS = [
        'B' => 'mg/100g',
        'CaO' => 'mg/100g',
        'CEC' => 'meq/100g',
        'Cu' => 'mg/100g',
        'Fe' => 'mg/100g',
        'FU' => 'mg/100g',
        'K2O' => 'mg/100g',
        'MgO' => 'mg/100g',
        'Mn' => 'mg/100g',
        'NH4-N' => 'mg/100g',
        'NO3-N' => 'mg/100g',
        'P2O5' => 'mg/100g',
        'PA' => 'mg/100g',
        'SiO2' => 'mg/100g',
        'Zn' => 'mg/100g',
        '易還元性マンガン' => 'mg/100g',
        '遊離酸化鉄' => 'mg/100g',
    ];

    /**
     * @return array<string, string>
     */
    public static function map(): array
    {
        return self::UNITS;
    }

    /**
     * @return list<string>
     */
    public static function allowedNames(): array
    {
        return array_keys(self::UNITS);
    }

    public static function isAllowed(string $name): bool
    {
        return array_key_exists($name, self::UNITS);
    }

    public static function unitFor(string $parameterName): ?string
    {
        return self::UNITS[$parameterName] ?? null;
    }

    public static function displayUnit(string $parameterName, ?string $stored = null): ?string
    {
        return self::UNITS[$parameterName] ?? self::nullIfEmpty($stored);
    }

    private static function nullIfEmpty(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }
}
