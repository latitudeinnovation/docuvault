<?php

namespace App\Console\Commands;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillGeneralDocuments extends Command
{
    protected $signature = 'documents:backfill-general
        {--force : Apply the changes (otherwise runs as a dry-run)}';

    protected $description = 'Strip AI extraction results from General documents so they show the uploaded file only.';

    public function handle(): int
    {
        $apply = (bool) $this->option('force');

        // General documents that still carry extraction artifacts from before
        // General docs skipped extraction. Company links are left untouched.
        $query = Document::query()
            ->where('document_type', DocumentType::General->value)
            ->where(function ($q): void {
                $q->whereHas('extractedFields')
                    ->orWhereNotNull('ai_raw_json')
                    ->orWhereNotNull('ai_confidence')
                    ->orWhereNotNull('processed_at')
                    ->orWhere('status', '!=', DocumentStatus::Uploaded->value);
            });

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No General documents need cleanup.');

            return self::SUCCESS;
        }

        if (! $apply) {
            $this->warn("Dry run: {$total} General document(s) would have their extracted results removed.");
            $this->line('Re-run with --force to apply.');

            return self::SUCCESS;
        }

        $cleaned = 0;

        $query->select('id')->chunkById(100, function ($documents) use (&$cleaned): void {
            foreach ($documents as $document) {
                DB::transaction(function () use ($document): void {
                    $document->extractedFields()->delete();

                    $document->forceFill([
                        'status' => DocumentStatus::Uploaded,
                        'ai_raw_json' => null,
                        'ai_confidence' => null,
                        'processed_at' => null,
                        'failure_reason' => null,
                    ])->save();
                });

                $cleaned++;
            }
        });

        $this->info("Removed extracted results from {$cleaned} General document(s).");

        return self::SUCCESS;
    }
}
