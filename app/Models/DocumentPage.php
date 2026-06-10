<?php

namespace App\Models;

use Database\Factories\DocumentPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['document_id', 'page_no', 'image_path', 'ocr_text'])]
class DocumentPage extends Model
{
    /** @use HasFactory<DocumentPageFactory> */
    use HasFactory;

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
