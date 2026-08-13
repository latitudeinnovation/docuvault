<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DiagnoseDocuments extends Command
{
    protected $signature = 'documents:diagnose
        {--search= : Only documents whose title contains this text}
        {--company= : Filter by company id}
        {--type= : Filter by document_type (e.g. bank_account)}';

    protected $description = 'List documents with their computed month tab and status, to spot mis-grouped, failed, or misclassified statements.';

    public function handle(): int
    {
        $query = Document::query()->with('extractedFields')->orderBy('title');

        if ($search = $this->option('search')) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        if ($company = $this->option('company')) {
            $query->where('company_id', $company);
        }

        if ($type = $this->option('type')) {
            $query->where('document_type', $type);
        }

        $documents = $query->get();

        if ($documents->isEmpty()) {
            $this->warn('No matching documents.');

            return self::SUCCESS;
        }

        $fallbacks = 0;
        $rows = $documents->map(function (Document $document) use (&$fallbacks): array {
            $period = $document->periodDate();

            // periodDate() returns processed_at when no statement period parsed —
            // these are the ones grouped under the upload-month tab.
            $fellBack = $period !== null
                && $document->processed_at !== null
                && $period->equalTo($document->processed_at);

            if ($fellBack) {
                $fallbacks++;
            }

            return [
                $document->getKey(),
                Str::limit((string) $document->title, 34),
                (string) $document->document_type,
                $document->status?->value ?? (string) $document->status,
                $document->periodLabel(),
                $fellBack ? 'upload-date fallback' : 'from statement',
            ];
        })->all();

        $this->table(['ID', 'Title', 'Type', 'Status', 'Month tab', 'Period source'], $rows);

        $byStatus = $documents
            ->groupBy(fn (Document $d): string => $d->status?->value ?? (string) $d->status)
            ->map->count();

        $this->newLine();
        $this->info($documents->count().' document(s).');
        $this->line('By status: '.$byStatus->map(fn ($n, $s): string => "{$s}={$n}")->implode(', '));
        $this->line("Grouped under the upload-month tab (period not parsed): {$fallbacks}");

        return self::SUCCESS;
    }
}
