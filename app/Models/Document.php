<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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
            'statement_period_end',
        ];

        foreach ($candidateKeys as $key) {
            $field = $this->extractedFields
                ->first(fn (ExtractedField $f): bool => str_contains(strtolower((string) $f->field_key), $key));

            $value = trim((string) ($field?->corrected_value ?? $field?->value ?? ''));

            if ($value !== '') {
                try {
                    return Carbon::parse($value);
                } catch (\Throwable) {
                    // Unparseable date string — try the next candidate.
                }
            }
        }

        return $this->processed_at;
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
