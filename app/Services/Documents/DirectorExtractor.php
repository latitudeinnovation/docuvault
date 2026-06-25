<?php

namespace App\Services\Documents;

use App\Models\Company;
use App\Models\Director;
use App\Models\Document;
use Illuminate\Support\Carbon;

class DirectorExtractor
{
    /**
     * Parse the officers table from a document's extracted result and attach the
     * people to the given company (deduping each person across documents by
     * IC/passport, then name). No-op when there is no recognisable table.
     */
    public function syncFromDocument(Document $document, Company $company): void
    {
        foreach ($this->officerRows($document) as $row) {
            $person = $this->parseRow($row);

            if ($person === null || ! $this->isDirector($person['designation'])) {
                continue;
            }

            $director = Director::firstOrCreate(
                ['user_id' => $document->user_id, 'slug' => Director::slugFor($person['ic'], $person['name'])],
                ['name' => $person['name'], 'ic_passport' => $person['ic'], 'address' => $person['address']],
            );

            // Backfill IC/address from a later document when initially missing.
            $director->forceFill([
                'ic_passport' => $director->ic_passport ?: $person['ic'],
                'address' => $director->address ?: $person['address'],
            ])->save();

            $company->directors()->syncWithoutDetaching([
                $director->getKey() => [
                    'designation' => $person['designation'],
                    'appointed_at' => $person['appointed_at'],
                    'source_document_id' => $document->getKey(),
                ],
            ]);
        }
    }

    /**
     * The rows of the first table whose name matches a configured keyword.
     *
     * @return array<int, array<string, mixed>>
     */
    private function officerRows(Document $document): array
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
        $tableKeys = config('docuvault.directors.table_keys', []);

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
     * Map a single table row onto a person, or null when it has no usable name.
     *
     * @param  array<string, mixed>  $row
     * @return array{name: string, ic: ?string, designation: ?string, appointed_at: ?string, address: ?string}|null
     */
    private function parseRow(array $row): ?array
    {
        /** @var array<string, array<int, string>> $columns */
        $columns = config('docuvault.directors.columns', []);

        $nameCol = $this->matchColumn($row, $columns['name'] ?? []);
        $addressCol = $this->matchColumn($row, $columns['address'] ?? []);

        $nameCell = $this->cell($row, $nameCol);
        [$name, $addressFromName] = $this->splitNameAddress($nameCell);

        if ($name === '') {
            return null;
        }

        // When name and address share one "Name/Address" cell, use the split-off
        // address; otherwise read the dedicated address column.
        $address = ($addressCol !== null && $addressCol !== $nameCol)
            ? $this->cell($row, $addressCol)
            : $addressFromName;

        return [
            'name' => $name,
            'ic' => $this->blankToNull($this->cell($row, $this->matchColumn($row, $columns['ic'] ?? []))),
            'designation' => $this->blankToNull($this->cell($row, $this->matchColumn($row, $columns['designation'] ?? []))),
            'appointed_at' => $this->parseDate($this->cell($row, $this->matchColumn($row, $columns['appointed'] ?? []))),
            'address' => $this->blankToNull($address),
        ];
    }

    /**
     * Return the original key of the first column whose header contains one of
     * the matchers (matchers checked in priority order).
     *
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
     * Resolve a cell to a trimmed string, unwrapping {value, confidence} and lists.
     *
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

    /**
     * Split a "name then address lines" cell into [name, address].
     *
     * @return array{0: string, 1: ?string}
     */
    private function splitNameAddress(string $cell): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $cell) ?: [])));

        if ($lines === []) {
            return ['', null];
        }

        $name = array_shift($lines);
        $address = $lines === [] ? null : implode(', ', $lines);

        return [$name, $address];
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Throwable) {
                // Try the next format.
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Whether a designation counts as a director, per configured keywords.
     */
    private function isDirector(?string $designation): bool
    {
        $designation = strtolower((string) $designation);

        if ($designation === '') {
            return false;
        }

        /** @var array<int, string> $allowed */
        $allowed = config('docuvault.directors.designations', ['director']);

        foreach ($allowed as $keyword) {
            if (str_contains($designation, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
