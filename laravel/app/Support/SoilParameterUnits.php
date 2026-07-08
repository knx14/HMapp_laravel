<?php

namespace App\Support;

class SoilParameterUnits
{
    /**
     * 推論パイプライン出力と整合する17パラメータの単位マップ。
     *
     * @var array<string, string|null>
     */
    private const UNITS = [
        'B' => 'mg/kg',
        'CaO' => 'mg/kg',
        'CEC' => 'cmol/kg',
        'Cu' => 'mg/kg',
        'Fe' => 'mg/kg',
        'FU' => null,
        'K2O' => 'mg/kg',
        'MgO' => 'mg/kg',
        'Mn' => 'mg/kg',
        'NH4-N' => 'mg/kg',
        'NO3-N' => 'mg/kg',
        'P2O5' => 'mg/kg',
        'PA' => null,
        'SiO2' => 'mg/kg',
        'Zn' => 'mg/kg',
        '易還元性マンガン' => 'mg/kg',
        '遊離酸化鉄' => 'mg/kg',
    ];

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
}
