<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\Documents\DirectorExtractor;
use App\Services\Documents\DocumentClassifier;
use Illuminate\Console\Command;

class BackfillDocumentCompanies extends Command
{
    protected $signature = 'documents:backfill-companies
        {--type : Also re-detect the document_type from the stored AI result}';

    protected $description = 'Link already-extracted documents to companies (and optionally re-detect their type).';

    public function handle(DocumentClassifier $classifier, DirectorExtractor $directors): int
    {
        $detectType = (bool) $this->option('type');
        $linked = 0;

        Document::query()
            ->whereHas('extractedFields')
            ->with('extractedFields')
            ->chunkById(100, function ($documents) use ($classifier, $directors, $detectType, &$linked): void {
                foreach ($documents as $document) {
                    $attributes = [];

                    if ($company = $classifier->resolveCompany($document)) {
                        $attributes['company_id'] = $company->getKey();
                        $directors->syncFromDocument($document, $company);
                    }

                    if ($detectType) {
                        $normalized = data_get($document->ai_raw_json, 'normalized_result', $document->ai_raw_json ?? []);
                        $attributes['document_type'] = $classifier->resolveType(is_array($normalized) ? $normalized : []);
                    }

                    if ($attributes !== []) {
                        $document->forceFill($attributes)->save();
                        $linked++;
                    }
                }
            });

        $this->info("Processed documents. Updated {$linked} document(s).");

        return self::SUCCESS;
    }
}
