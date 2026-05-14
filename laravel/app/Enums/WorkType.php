<?php

namespace App\Enums;

enum WorkType: string
{
    case Fertilization = 'fertilization';
    case Tillage = 'tillage';
    case Pesticide = 'pesticide';
    case Harvest = 'harvest';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Fertilization => '施肥',
            self::Tillage => '耕うん',
            self::Pesticide => '農薬',
            self::Harvest => '収穫',
            self::Other => 'その他',
        };
    }
}
