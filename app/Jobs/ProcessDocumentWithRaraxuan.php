<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Enums\ExtractedFieldStatus;
use App\Models\Document;
use App\Services\Documents\DocumentClassifier;
use App\Services\Raraxuan\DocumentExtractionClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProcessDocumentWithRaraxuan implements ShouldQueue
{
    use Queueable;

    public function __construct(public Document $document) {}

    public function handle(): void
    {
        $document = $this->document->fresh();

        if (! $document instanceof Document) {
            return;
        }

        try {
            $this->process($document);
        } catch (Throwable $exception) {
            report($exception);

            $document->forceFill([
                'status' => DocumentStatus::Failed,
                'failure_reason' => $exception->getMessage(),
            ])->save();
        }
    }

    public function processNow(): void
    {
        $document = $this->document->fresh();

        if (! $document instanceof Document) {
            throw new \RuntimeException('Document no longer exists.');
        }

        $this->process($document);
    }

    private function process(Document $document): void
    {
        $document->forceFill([
            'status' => DocumentStatus::Processing,
            'failure_reason' => null,
        ])->save();

        $disk = Storage::disk($document->file_disk);

        if (! $disk->exists($document->file_path)) {
            throw new \RuntimeException("Document file [{$document->file_path}] was not found on disk [{$document->file_disk}].");
        }

        $contents = (string) $disk->get($document->file_path);
        $filename = $document->original_file_name ?: basename($document->file_path);

        $response = app(DocumentExtractionClient::class)->extract(
            config('docuvault.raraxuan.document_agent'),
            ['filename' => $filename],
            $contents,
            $filename,
            $document->file_type ?: 'application/octet-stream',
        );

        $normalizedResponse = $this->normalizeResponse($response);

        $classifier = app(DocumentClassifier::class);

        DB::transaction(function () use ($document, $normalizedResponse, $response, $classifier): void {
            $document->extractedFields()->delete();

            foreach ($this->extractFields($normalizedResponse) as $field) {
                $document->extractedFields()->create([
                    'field_key' => (string) Arr::get($field, 'key', Arr::get($field, 'field_key', 'unknown')),
                    'field_label' => (string) Arr::get($field, 'label', Arr::get($field, 'field_label', 'Unknown')),
                    'value' => Arr::get($field, 'value'),
                    'confidence' => Arr::get($field, 'confidence'),
                    'status' => ExtractedFieldStatus::Pending,
                ]);
            }

            $company = $classifier->resolveCompany($document);

            $document->forceFill([
                'company_id' => $company?->getKey(),
                'document_type' => $classifier->resolveType($normalizedResponse),
                'status' => DocumentStatus::NeedsReview,
                'ai_confidence' => $this->overallConfidence($normalizedResponse),
                'ai_raw_json' => array_replace($response, [
                    'normalized_result' => $normalizedResponse,
                ]),
                'processed_at' => now(),
            ])->save();
        });
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function normalizeResponse(array $response): array
    {
        $result = data_get($response, 'data.result');

        if (is_string($result)) {
            $decodedResult = json_decode($result, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedResult)) {
                return $decodedResult;
            }
        }

        return $response;
    }

    /**
     * Turn the AI result into readable key => value field rows.
     *
     * The template wraps the extracted values in an `extracted_fields` object
     * alongside schema scaffolding (document_type, total_pages, tables, ...).
     * When that container is present we surface only its contents, so the
     * review table shows the marked/annotated values and not the envelope.
     * Otherwise we fall back to flattening the whole response. Nested keys
     * become dotted paths, scalar lists are joined, empty containers skipped.
     *
     * @param  array<string, mixed>  $response
     * @return array<int, array<string, mixed>>
     */
    private function extractFields(array $response, string $prefix = ''): array
    {
        if ($prefix === '') {
            $marked = data_get($response, 'extracted_fields', data_get($response, 'data.extracted_fields'));
            $tables = data_get($response, 'tables', data_get($response, 'data.tables'));

            $source = \is_array($marked) ? $marked : [];

            if (\is_array($tables)) {
                foreach ($tables as $i => $table) {
                    $name = (string) data_get($table, 'table_name', 'table_'.$i);
                    $rows = data_get($table, 'data', $table);

                    if (\is_array($rows) && $rows !== []) {
                        $indexed = [];
                        foreach ($rows as $row) {
                            if (! \is_array($row)) {
                                $indexed[] = $row;
                                continue;
                            }
                            [$categoryVal, $categoryCol] = $this->findRowCategory($row);
                            if ($categoryVal !== null && $categoryCol !== null) {
                                $indexed[$categoryVal] = array_diff_key($row, [$categoryCol => null]);
                            } else {
                                $indexed[] = $row;
                            }
                        }
                        $source[$name] = $indexed;
                    }
                }
            }

            if ($source !== []) {
                $response = $source;
            }
        }

        $rows = [];

        foreach ($response as $key => $value) {
            $safeKey = str_replace('.', '', (string) $key);
            $path = $prefix === '' ? $safeKey : $prefix.'.'.$safeKey;

            if (\is_array($value)) {
                if ($value === []) {
                    continue;
                }

                // Per-field confidence shape: {"value": ..., "confidence": 0.9}
                if (array_key_exists('value', $value) && array_key_exists('confidence', $value) && count($value) === 2) {
                    $rows[] = $this->fieldRow($path, $value['value'], is_numeric($value['confidence']) ? (float) $value['confidence'] : null, (string) $key);

                    continue;
                }

                // Per-row confidence shape: {"COL1": "val", ..., "confidence": 0.9}
                if (! array_is_list($value) && array_key_exists('confidence', $value) && count($value) > 2) {
                    $rowConfidence = is_numeric($value['confidence']) ? (float) $value['confidence'] : null;
                    foreach (array_diff_key($value, ['confidence' => null]) as $col => $colVal) {
                        $safeCol = str_replace('.', '', (string) $col);
                        $rows[] = $this->fieldRow($path.'.'.$safeCol, \is_array($colVal) ? json_encode($colVal) : $colVal, $rowConfidence, (string) $col);
                    }

                    continue;
                }

                if (array_is_list($value) && $this->isScalarList($value)) {
                    $rows[] = $this->fieldRow($path, implode(', ', array_map(
                        static fn (mixed $item): string => (string) $item,
                        $value,
                    )), null, (string) $key);

                    continue;
                }

                $rows = array_merge($rows, $this->extractFields($value, $path));

                continue;
            }

            $rows[] = $this->fieldRow($path, $value, null, (string) $key);
        }

        return $rows;
    }

    /**
     * Find the row identifier using the first column whose value is not a pure
     * numeric/ID string (digits, dashes, slashes). Returns [englishValue, columnKey].
     *
     * @param  array<string, mixed>  $row
     * @return array{0: string|null, 1: string|null}
     */
    private function findRowCategory(array $row): array
    {
        foreach ($row as $col => $val) {
            if ((string) $col === 'confidence') {
                continue;
            }
            $english = trim(explode('/', (string) $val)[0]);
            if ($english === '') {
                continue;
            }
            // Skip pure ID/numeric values (IC numbers, dates like "720101-10-6249")
            if (preg_match('/^[\d\-\/\s]+$/', $english)) {
                continue;
            }
            return [$english, (string) $col];
        }

        return [null, null];
    }

    /**
     * @param  array<int, mixed>  $value
     */
    private function isScalarList(array $value): bool
    {
        foreach ($value as $item) {
            if (\is_array($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldRow(string $path, mixed $value, ?float $confidence = null, ?string $columnName = null): array
    {
        $labelSource = $columnName ?? end(explode('.', $path));
        // Strip secondary language (e.g. "BALANCE / BAKI" → "Balance")
        $english = trim(explode('/', $labelSource)[0]);
        $source = $english !== '' ? $english : $labelSource;
        $label = Str::headline(mb_strtolower($source));

        return [
            'key' => $path,
            'label' => $label,
            'value' => $value === null ? null : (string) $value,
            'confidence' => $confidence,
        ];
    }

    /**
     * @param  array<string, mixed>  $normalized
     */
    private function overallConfidence(array $normalized): ?float
    {
        $value = data_get($normalized, 'overall_confidence');

        return is_numeric($value) ? round((float) $value, 4) : null;
    }

}
