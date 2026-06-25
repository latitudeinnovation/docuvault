<?php

namespace App\Models;

use App\Enums\DocumentType;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'name',
    'slug',
    'registration_no',
])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function directors(): BelongsToMany
    {
        return $this->belongsToMany(Director::class)
            ->withPivot(['designation', 'appointed_at', 'source_document_id'])
            ->withTimestamps();
    }

    public function shareholders(): BelongsToMany
    {
        return $this->belongsToMany(Shareholder::class)
            ->withPivot(['shares', 'source_document_id'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documentsOfType(DocumentType|string $type): HasMany
    {
        $value = $type instanceof DocumentType ? $type->value : $type;

        return $this->documents()->where('document_type', $value);
    }

    /**
     * Build a stable slug used to dedupe companies by name within an owner.
     */
    public static function slugFor(string $name): string
    {
        return Str::slug($name) ?: Str::slug(Str::ascii($name)) ?: md5($name);
    }
}
