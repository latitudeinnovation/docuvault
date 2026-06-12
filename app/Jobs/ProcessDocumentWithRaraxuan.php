<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Enums\ExtractedFieldStatus;
use App\Models\Document;
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

        DB::transaction(function () use ($document, $normalizedResponse, $response): void {
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

            $document->forceFill([
                'status' => DocumentStatus::NeedsReview,
                'ai_confidence' => $this->completenessScore($normalizedResponse),
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
                        $source[$name] = $rows;
                    }
                }
            }

            if ($source !== []) {
                $response = $source;
            }
        }

        $rows = [];

        foreach ($response as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (\is_array($value)) {
                if ($value === []) {
                    continue;
                }

                if (array_is_list($value) && $this->isScalarList($value)) {
                    $rows[] = $this->fieldRow($path, implode(', ', array_map(
                        static fn (mixed $item): string => (string) $item,
                        $value,
                    )));

                    continue;
                }

                $rows = array_merge($rows, $this->extractFields($value, $path));

                continue;
            }

            $rows[] = $this->fieldRow($path, $value);
        }

        return $rows;
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
    private function fieldRow(string $path, mixed $value): array
    {
        $segments = explode('.', $path);

        return [
            'key' => $path,
            'label' => Str::headline((string) end($segments)),
            'value' => $value === null ? null : (string) $value,
            'confidence' => null,
        ];
    }

    /**
     * Deterministic data-coverage score: ratio of non-null scalar leaves across the
     * extracted_fields + tables of the normalized result. Replaces the model's
     * unreliable self-reported overall_confidence.
     *
     * @param  array<string, mixed>  $normalized
     */
    private function completenessScore(array $normalized): ?float
    {
        $source = [];

        if (\is_array($ef = data_get($normalized, 'extracted_fields'))) {
            $source['extracted_fields'] = $ef;
        }

        if (\is_array($tb = data_get($normalized, 'tables'))) {
            $source['tables'] = $tb;
        }

        if ($source === []) {
            $source = $normalized;
        }

        [$filled, $total] = $this->countLeaves($source);

        return $total > 0 ? round($filled / $total, 4) : null;
    }

    /**
     * Count scalar leaves of a nested structure. A leaf counts as filled when it is
     * not null and not an empty/whitespace string.
     *
     * @return array{0: int, 1: int} [filled, total]
     */
    private function countLeaves(mixed $value): array
    {
        if (\is_array($value)) {
            $filled = 0;
            $total = 0;

            foreach ($value as $item) {
                [$f, $t] = $this->countLeaves($item);
                $filled += $f;
                $total += $t;
            }

            return [$filled, $total];
        }

        $isFilled = ! ($value === null || (\is_string($value) && trim($value) === ''));

        return [$isFilled ? 1 : 0, 1];
    }
}
