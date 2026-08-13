<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'company_id',
    'title',
    'document_type',
    'file_disk',
    'file_path',
    'original_file_name',
    'file_type',
    'status',
    'ai_confidence',
    'ai_raw_json',
    'processed_at',
    'failure_reason',
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(DocumentPage::class);
    }

    public function extractedFields(): HasMany
    {
        return $this->hasMany(ExtractedField::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(DocumentNote::class);
    }

    /**
     * Whether this document should be sent to the AI for field extraction.
     * General documents are stored as-is (just the uploaded PDF/image) and
     * never extracted, so processing them is a no-op.
     */
    public function shouldExtract(): bool
    {
        return $this->document_type !== DocumentType::General->value;
    }

    /**
     * The date this document "covers" — for a bank statement, the start of its
     * statement period; otherwise its document date, falling back to when it
     * was processed. Used to group statements by month.
     */
    public function periodDate(): ?Carbon
    {
        $candidateKeys = [
            'statement_period_start',
            'period_start',
            'statement_date',
            'document_date',
            'statement_period',
            'statement_period_end',
        ];

        foreach ($candidateKeys as $key) {
            $field = $this->extractedFields
                ->first(fn (ExtractedField $f): bool => str_contains($this->normalizeFieldKey((string) $f->field_key), $key));

            $value = trim((string) ($field?->corrected_value ?? $field?->value ?? ''));

            if ($value === '') {
                continue;
            }

            // Statement periods arrive as ranges — "01 February 2024 To 29
            // February 2024", "16 Jan 25 – 31 Jan 25". Keep the start date
            // only. Whitespace is required around a dash so a day-first single
            // date like "31-12-2023" is not split mid-value.
            if (preg_match('/^(.+?)\s+(?:to|till|until|[-–—])\s+/iu', $value, $m)) {
                $value = trim($m[1]);
            }

            if ($parsed = $this->parseDateValue($value)) {
                return $parsed;
            }
        }

        return $this->processed_at;
    }

    /**
     * Normalize an extracted field key for matching: last dotted segment,
     * lowercased, non-alphanumerics collapsed to underscores. Mirrors
     * BankAccountExtractor so a key arriving as "Statement Period" or
     * "ACCOUNT SUMMARY.STATEMENT PERIOD" still matches "statement_period".
     */
    private function normalizeFieldKey(string $key): string
    {
        $segment = Str::afterLast($key, '.');

        return trim(preg_replace('/[^a-z0-9]+/', '_', Str::lower($segment)) ?? '', '_');
    }

    /**
     * Parse a raw extracted date string that may be in day-first slash/dash
     * notation (e.g. "31/12/2023", as used by MY bank statements) — a format
     * Carbon::parse() misreads as US month-first and rejects — before falling
     * back to Carbon's general parser for other formats (e.g. "29 February 2024").
     */
    private function parseDateValue(string $value): ?Carbon
    {
        foreach (['d/m/Y', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->startOfDay();
            } catch (\Throwable) {
                // Doesn't match this explicit format — try the next one.
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Human label for the document's period, e.g. "February 2024".
     */
    public function periodLabel(): string
    {
        return $this->periodDate()?->translatedFormat('F Y') ?? 'Undated';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'ai_confidence' => 'decimal:4',
            'ai_raw_json' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
