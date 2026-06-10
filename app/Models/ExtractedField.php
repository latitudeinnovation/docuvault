<?php

namespace App\Models;

use App\Enums\ExtractedFieldStatus;
use Database\Factories\ExtractedFieldFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'document_id',
    'field_key',
    'field_label',
    'value',
    'confidence',
    'corrected_value',
    'status',
])]
class ExtractedField extends Model
{
    /** @use HasFactory<ExtractedFieldFactory> */
    use HasFactory;

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:4',
            'status' => ExtractedFieldStatus::class,
        ];
    }
}
