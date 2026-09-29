<?php

namespace App\Services\Measurements;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * S3 上のセンサー生データ CSV（1行に実数150個 → 虚数150個）を読む。
 */
class RawMeasurementReader
{
    public const VALUES_PER_PART = 150;
    public const VALUE_COUNT = self::VALUES_PER_PART * 2;

    /** @var array<string, Filesystem> */
    private array $disks = [];

    /**
     * 読めない場合は null を返し、ダウンロード全体は止めない。
     *
     * @return list<string>|null 300個の値（文字列のまま）
     */
    public function read(int $uploadId, ?string $filePath): ?array
    {
        if ($filePath === null || $filePath === '') {
            return null;
        }

        try {
            [$disk, $key] = $this->resolve($filePath);
            $contents = $disk->get($key);
        } catch (\Throwable $e) {
            Log::warning('measurement raw data could not be read', [
                'upload_id' => $uploadId,
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if ($contents === null) {
            Log::warning('measurement raw data not found', ['upload_id' => $uploadId, 'file_path' => $filePath]);

            return null;
        }

        $values = $this->parse($contents);
        if (count($values) !== self::VALUE_COUNT) {
            Log::warning('measurement raw data has unexpected column count', [
                'upload_id' => $uploadId,
                'file_path' => $filePath,
                'count' => count($values),
            ]);

            return null;
        }

        return $values;
    }

    /**
     * @return list<string>
     */
    private function parse(string $contents): array
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
        $line = strtok(trim($contents), "\r\n");
        if ($line === false || $line === '') {
            return [];
        }

        return array_map('trim', str_getcsv($line));
    }

    /**
     * file_path は `s3://バケット/キー` かキーのみ。
     *
     * @return array{0: Filesystem, 1: string}
     */
    private function resolve(string $filePath): array
    {
        if (!preg_match('#^s3://([^/]+)/(.+)$#', $filePath, $m)) {
            return [Storage::disk('s3'), ltrim($filePath, '/')];
        }

        [, $bucket, $key] = $m;

        if ($bucket === config('filesystems.disks.s3.bucket')) {
            return [Storage::disk('s3'), $key];
        }

        return [$this->disks[$bucket] ??= Storage::build(array_merge(config('filesystems.disks.s3'), ['bucket' => $bucket])), $key];
    }
}
