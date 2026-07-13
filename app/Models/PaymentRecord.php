<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; 
class PaymentRecord extends Model
{
    protected $fillable = [
        'company_id',
        'transaction_type',
        'category',
        'bank_account',
        'main_account',
        'amount',
        'remarks',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
