<?php

namespace App\Models;

use Database\Factories\ShareholderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'name',
    'ic_passport',
    'slug',
])]
class Shareholder extends Model
{
    /** @use HasFactory<ShareholderFactory> */
    use HasFactory;

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['shares', 'source_document_id'])
            ->withTimestamps();
    }

    public static function slugFor(?string $ic, string $name): string
    {
        $digits = preg_replace('/\D+/', '', (string) $ic);

        if (is_string($digits) && $digits !== '') {
            return 'ic-'.$digits;
        }

        return Str::slug($name) ?: md5($name);
    }
}
