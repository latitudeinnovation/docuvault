<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Enums\ExtractedFieldStatus;
use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LatitudeInnovation\Raraxuan\Facades\Raraxuan;
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

        $response = Raraxuan::agent(
            config('docuvault.raraxuan.document_agent'),
            [
                'filename' => $document->original_file_name ?: basename($document->file_path),
            ]
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
                'ai_confidence' => $this->extractConfidence($normalizedResponse),
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
     * @return array<int, array<string, mixed>>
     */
    private function extractFields(array $response): array
    {
        $fields = data_get($response, 'fields', data_get($response, 'data.fields', []));

        if (! is_array($fields)) {
            return [];
        }

        if (array_is_list($fields)) {
            return array_values(array_filter($fields, is_array(...)));
        }

        return collect($fields)
            ->map(fn (mixed $field, string $key): array => is_array($field)
                ? [
                    'key' => $key,
                    'label' => Str::headline($key),
                    'value' => Arr::get($field, 'value'),
                    'confidence' => Arr::get($field, 'confidence'),
                ]
                : [
                    'key' => $key,
                    'label' => Str::headline($key),
                    'value' => $field,
                    'confidence' => null,
                ]
            )
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function extractConfidence(array $response): mixed
    {
        return data_get(
            $response,
            'overall_confidence',
            data_get($response, 'confidence', data_get($response, 'data.confidence')),
        );
    }
}
