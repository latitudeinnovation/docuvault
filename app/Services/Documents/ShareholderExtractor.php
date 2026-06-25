<?php

namespace App\Services\Documents;

use App\Models\Company;
use App\Models\Document;
use App\Models\Shareholder;

class ShareholderExtractor
{
    /**
     * Parse the shareholders table from a document's extracted result and attach
     * them to the given company, deduping by IC/passport then name.
     */
    public function syncFromDocument(Document $document, Company $company): void
    {
        foreach ($this->shareholderRows($document) as $row) {
            $person = $this->parseRow($row);

            if ($person === null) {
                continue;
            }

            $shareholder = Shareholder::firstOrCreate(
                ['user_id' => $document->user_id, 'slug' => Shareholder::slugFor($person['ic'], $person['name'])],
                ['name' => $person['name'], 'ic_passport' => $person['ic']],
            );

            $shareholder->forceFill([
                'ic_passport' => $shareholder->ic_passport ?: $person['ic'],
            ])->save();

            $company->shareholders()->syncWithoutDetaching([
                $shareholder->getKey() => [
                    'shares' => $person['shares'],
                    'source_document_id' => $document->getKey(),
                ],
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function shareholderRows(Document $document): array
    {
        $raw = $document->ai_raw_json;

        if (! is_array($raw)) {
            return [];
        }

        $tables = data_get($raw, 'normalized_result.tables', data_get($raw, 'tables', []));

        if (! is_array($tables)) {
            return [];
        }

        /** @var array<int, string> $tableKeys */
        $tableKeys = config('docuvault.shareholders.table_keys', []);

        foreach ($tables as $table) {
            $name = strtolower((string) data_get($table, 'table_name', ''));

            foreach ($tableKeys as $keyword) {
                if ($name !== '' && str_contains($name, strtolower($keyword))) {
                    $rows = data_get($table, 'data', []);

                    return array_values(array_filter(is_array($rows) ? $rows : [], 'is_array'));
                }
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{name: string, ic: ?string, shares: ?string}|null
     */
    private function parseRow(array $row): ?array
    {
        /** @var array<string, array<int, string>> $columns */
        $columns = config('docuvault.shareholders.columns', []);

        $nameCol = $this->matchColumn($row, $columns['name'] ?? []);
        $name = trim($this->cell($row, $nameCol));

        if ($name === '') {
            return null;
        }

        return [
            'name' => $name,
            'ic' => $this->blankToNull($this->cell($row, $this->matchColumn($row, $columns['ic'] ?? []))),
            'shares' => $this->blankToNull($this->cell($row, $this->matchColumn($row, $columns['shares'] ?? []))),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $matchers
     */
    private function matchColumn(array $row, array $matchers): ?string
    {
        foreach ($matchers as $matcher) {
            foreach (array_keys($row) as $column) {
                if (str_contains(strtolower((string) $column), strtolower($matcher))) {
                    return (string) $column;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function cell(array $row, ?string $column): string
    {
        if ($column === null || ! array_key_exists($column, $row)) {
            return '';
        }

        $value = $row[$column];

        if (is_array($value)) {
            if (array_key_exists('value', $value)) {
                $value = $value['value'];
            } elseif (array_is_list($value)) {
                $value = implode("\n", $value);
            } else {
                $value = reset($value);
            }
        }

        return trim((string) $value);
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
