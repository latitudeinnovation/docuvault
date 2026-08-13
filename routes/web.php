<?php

use App\Models\Document;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect('/admin');
});

// Streams a document's uploaded file inline (for the in-page preview). Behind
// auth since files live on the private disk and have no public URL.
Route::middleware('auth')
    ->get('documents/{document}/preview', function (Document $document) {
        $disk = Storage::disk($document->file_disk);

        abort_unless($disk->exists($document->file_path), 404);

        return $disk->response(
            $document->file_path,
            $document->original_file_name ?: basename($document->file_path),
            ['Content-Type' => $document->file_type ?: 'application/octet-stream'],
        );
    })
    ->name('documents.preview');
