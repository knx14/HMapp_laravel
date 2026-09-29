<?php

namespace App\Services\Measurements;

use App\Models\AnalysisResult;
use App\Models\ResultValue;
use App\Models\Upload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * 測定データ閲覧の CSV を1行ずつ書き出す。管理者は削除日時と生データ300列を付ける。
 */
class MeasurementCsvExporter
{
    public const PARAMETERS = ['CEC', 'CaO', 'MgO', 'K2O'];

    private const CHUNK_SIZE = 200;

    public function __construct(private RawMeasurementReader $rawReader) {}

    /**
     * @return list<string>
     */
    public function header(bool $includeRaw): array
    {
        $header = ['圃場名', '栽培方式', '作物種別', '測定番号', '測定日', '緯度', '経度', ...self::PARAMETERS];

        if (!$includeRaw) {
            return $header;
        }

        $header[] = '削除日時';
        for ($i = 1; $i <= RawMeasurementReader::VALUES_PER_PART; $i++) {
            $header[] = "実数_{$i}";
        }
        for ($i = 1; $i <= RawMeasurementReader::VALUES_PER_PART; $i++) {
            $header[] = "虚数_{$i}";
        }

        return $header;
    }

    /**
     * @param  resource  $out
     */
    public function write($out, Builder $query, bool $includeRaw): void
    {
        fwrite($out, "\xEF\xBB\xBF");
        $this->putRow($out, $this->header($includeRaw));

        $query->chunk(self::CHUNK_SIZE, function (Collection $uploads) use ($out, $includeRaw): void {
            [$pointsByUpload, $valuesByPoint] = $this->loadResults($uploads);

            foreach ($uploads as $upload) {
                $point = $pointsByUpload->get($upload->id);
                $values = $point ? $valuesByPoint->get($point->id, collect()) : collect();

                $this->putRow($out, $this->row($upload, $point, $values, $includeRaw));
            }

            fflush($out);
        });
    }

    /**
     * @return list<string>
     */
    private function row(Upload $upload, ?AnalysisResult $point, Collection $values, bool $includeRaw): array
    {
        $row = [
            (string) $upload->farm_name,
            (string) $upload->cultivation_method,
            (string) $upload->crop_type,
            $upload->measurement_number === null ? '' : (string) $upload->measurement_number,
            $upload->measurement_date?->format('Y-m-d') ?? '',
            $this->number($point?->latitude),
            $this->number($point?->longitude),
        ];

        foreach (self::PARAMETERS as $parameter) {
            $row[] = $this->number($values->get($parameter));
        }

        if (!$includeRaw) {
            return $row;
        }

        $row[] = $upload->deleted_at
            ? $upload->deleted_at->copy()->timezone(config('measurements.display_timezone'))->format('Y-m-d H:i:s')
            : '';

        $raw = $upload->isManualEntry() ? null : $this->rawReader->read($upload->id, $upload->file_path);

        return [...$row, ...($raw ?? array_fill(0, RawMeasurementReader::VALUE_COUNT, ''))];
    }

    /**
     * @return array{0: Collection<int, AnalysisResult>, 1: Collection<int, Collection<string, float>>}
     */
    private function loadResults(Collection $uploads): array
    {
        $pointsByUpload = AnalysisResult::query()
            ->whereIn('upload_id', $uploads->pluck('id'))
            ->orderBy('id')
            ->get(['id', 'upload_id', 'latitude', 'longitude'])
            ->unique('upload_id')
            ->keyBy('upload_id');

        $valuesByPoint = ResultValue::query()
            ->whereIn('analysis_result_id', $pointsByUpload->pluck('id'))
            ->whereIn('parameter_name', self::PARAMETERS)
            ->get(['analysis_result_id', 'parameter_name', 'parameter_value'])
            ->groupBy('analysis_result_id')
            ->map(fn (Collection $rows) => $rows->pluck('parameter_value', 'parameter_name'));

        return [$pointsByUpload, $valuesByPoint];
    }

    private function number(mixed $value): string
    {
        return $value === null ? '' : (string) (float) $value;
    }

    /**
     * @param  resource  $out
     * @param  list<string>  $row
     */
    private function putRow($out, array $row): void
    {
        fputcsv($out, $row, ',', '"', '\\', "\r\n");
    }
}
