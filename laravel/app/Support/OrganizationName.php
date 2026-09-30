<?php

namespace App\Support;

/**
 * 所属名の保存形式。所属名の一致で圃場を共有するため、全角・半角の違いを揃えてから保存する。
 */
final class OrganizationName
{
    public const MAX_LENGTH = 100;

    /**
     * normalize() 後の値に掛ける入力ルール。
     *
     * @return list<string>
     */
    public static function rules(): array
    {
        return ['required', 'string', 'max:'.self::MAX_LENGTH];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(string $attribute = 'organization'): array
    {
        return [
            "{$attribute}.required" => '所属を入力してください。',
            "{$attribute}.string" => '所属を入力してください。',
            "{$attribute}.max" => '所属は'.self::MAX_LENGTH.'文字以内で入力してください。',
        ];
    }

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (class_exists(\Normalizer::class)) {
            $value = \Normalizer::normalize($value, \Normalizer::FORM_KC) ?: $value;
        } else {
            // intl が無い環境では、英数字・記号・スペースを半角に、半角カナを全角に揃える。
            $value = mb_convert_kana($value, 'asKV', 'UTF-8');
        }

        $value = preg_replace('/^[\s\x{3000}]+|[\s\x{3000}]+$/u', '', $value) ?? $value;

        return $value === '' ? null : $value;
    }
}
