<?php

namespace App\Services\Measurements;

use App\Models\AppUser;
use Illuminate\Http\Request;

/**
 * 測定データ閲覧の絞り込み条件。一般ユーザーには管理者用の条件を効かせない。
 */
final class MeasurementFilters
{
    public const TRASHED_WITHOUT = 'without';
    public const TRASHED_WITH = 'with';
    public const TRASHED_ONLY = 'only';

    public const TRASHED_OPTIONS = [
        self::TRASHED_WITHOUT => '含めない',
        self::TRASHED_WITH => '含める',
        self::TRASHED_ONLY => '削除済みのみ',
    ];

    public function __construct(
        public readonly ?string $farmName = null,
        public readonly ?string $cultivationMethod = null,
        public readonly ?string $cropType = null,
        public readonly ?string $userName = null,
        public readonly string $trashed = self::TRASHED_WITHOUT,
    ) {}

    public static function fromRequest(Request $request, AppUser $user): self
    {
        $isAdmin = $user->isAdmin();
        $trashed = (string) $request->input('trashed', self::TRASHED_WITHOUT);

        return new self(
            farmName: self::text($request->input('farm_name')),
            cultivationMethod: self::text($request->input('cultivation_method')),
            cropType: self::text($request->input('crop_type')),
            userName: $isAdmin ? self::text($request->input('user_name')) : null,
            trashed: $isAdmin && array_key_exists($trashed, self::TRASHED_OPTIONS) ? $trashed : self::TRASHED_WITHOUT,
        );
    }

    /**
     * 画面のフォームとページ送りに引き継ぐ値。
     *
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        return array_filter([
            'farm_name' => $this->farmName,
            'cultivation_method' => $this->cultivationMethod,
            'crop_type' => $this->cropType,
            'user_name' => $this->userName,
            'trashed' => $this->trashed === self::TRASHED_WITHOUT ? null : $this->trashed,
        ], fn ($value) => $value !== null);
    }

    private static function text(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
