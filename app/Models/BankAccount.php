<?php

namespace App\Models;

use Database\Factories\BankAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'company_id',
    'account_no',
    'slug',
    'account_holder_name',
    'bank_name',
    'account_type',
    'currency',
    'source_document_id',
])]
class BankAccount extends Model
{
    /** @use HasFactory<BankAccountFactory> */
    use HasFactory;

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'source_document_id');
    }

    /**
     * Dedupe key for an account number: digits only when present (stable
     * across formatting variance like dashes/spaces), else a slug of the
     * raw string.
     */
    public static function slugFor(string $accountNo): string
    {
        $digits = preg_replace('/\D+/', '', $accountNo);

        if (is_string($digits) && $digits !== '') {
            return $digits;
        }

        return Str::slug($accountNo) ?: md5($accountNo);
    }
}
