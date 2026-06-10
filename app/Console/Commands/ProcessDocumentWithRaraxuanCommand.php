<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDocumentWithRaraxuan;
use App\Models\Document;
use Illuminate\Console\Command;

class ProcessDocumentWithRaraxuanCommand extends Command
{
    protected $signature = 'documents:process-raraxuan
        {document : The document ID to process}
        {--sync : Run the job immediately instead of dispatching it to the queue}';

    protected $description = 'Process a document with Raraxuan manually.';

    public function handle(): int
    {
        $document = Document::query()->find($this->argument('document'));

        if (! $document instanceof Document) {
            $this->error('Document not found.');

            return self::FAILURE;
        }

        if ($this->option('sync')) {
            (new ProcessDocumentWithRaraxuan($document))->processNow();

            $this->info("Document [{$document->getKey()}] processed with Raraxuan.");

            return self::SUCCESS;
        }

        ProcessDocumentWithRaraxuan::dispatch($document);

        $this->info("Document [{$document->getKey()}] queued for Raraxuan processing.");

        return self::SUCCESS;
    }
}
