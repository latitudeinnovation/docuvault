<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
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
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
