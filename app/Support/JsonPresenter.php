<?php

namespace App\Support;

use App\Models\Document;
use Illuminate\Support\HtmlString;

/**
 * Renders extraction payloads as clean, human-readable JSON (pretty text,
 * compact text, or syntax-highlighted HTML). Shared by the document infolist
 * and the company view so the JSON format stays identical everywhere.
 */
class JsonPresenter
{
    /**
     * The decoded extraction result for a document (normalized_result when
     * present, otherwise the raw envelope).
     */
    public static function normalized(Document $record): mixed
    {
        $payload = $record->ai_raw_json;

        if (! \is_array($payload)) {
            return $payload;
        }

        return $payload['normalized_result'] ?? $payload;
    }

    /**
     * Pretty-print a payload, deep-decoding any nested JSON-encoded string
     * values so the output is clean JSON, not an escaped \n wall.
     */
    public static function pretty(mixed $payload): ?string
    {
        if (blank($payload)) {
            return null;
        }

        if (! \is_array($payload)) {
            return (string) $payload;
        }

        return json_encode(
            self::deepDecode($payload),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
        ) ?: null;
    }

    /**
     * Compact single-line JSON (still deep-decoded so there is no escaped \n wall).
     */
    public static function compact(mixed $payload): ?string
    {
        if (blank($payload)) {
            return null;
        }

        if (! \is_array($payload)) {
            return (string) $payload;
        }

        return json_encode(
            self::deepDecode($payload),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
        ) ?: null;
    }

    /**
     * Syntax-highlighted, pretty JSON as safe HTML. Keys, string values,
     * numbers, booleans and null each get their own colour.
     */
    public static function html(mixed $payload): ?HtmlString
    {
        if (blank($payload)) {
            return null;
        }

        return new HtmlString(self::renderJsonNode(self::deepDecode($payload), 0));
    }

    private static function renderJsonNode(mixed $value, int $depth): string
    {
        $pad = str_repeat('    ', $depth);
        $padInner = str_repeat('    ', $depth + 1);

        if (\is_array($value)) {
            if ($value === []) {
                return array_is_list($value) ? '[]' : '{}';
            }

            $isList = array_is_list($value);
            [$open, $close] = $isList ? ['[', ']'] : ['{', '}'];

            $lines = [];

            foreach ($value as $key => $item) {
                $line = $padInner;

                if (! $isList) {
                    $line .= '<span style="color:#0284c7">"'.e((string) $key).'"</span>'
                        .'<span style="color:#9ca3af">: </span>';
                }

                $line .= self::renderJsonNode($item, $depth + 1);
                $lines[] = $line;
            }

            return $open."\n".implode(",\n", $lines)."\n".$pad.$close;
        }

        if (\is_string($value)) {
            return '<span style="color:#047857;font-weight:600">"'.e($value).'"</span>';
        }

        if (\is_bool($value)) {
            return '<span style="color:#9333ea">'.($value ? 'true' : 'false').'</span>';
        }

        if ($value === null) {
            return '<span style="color:#e11d48">null</span>';
        }

        return '<span style="color:#d97706">'.e((string) $value).'</span>';
    }

    /**
     * Recursively replace string values that are themselves JSON objects/arrays
     * with their decoded form.
     */
    private static function deepDecode(mixed $value): mixed
    {
        if (\is_array($value)) {
            return array_map([self::class, 'deepDecode'], $value);
        }

        if (\is_string($value)) {
            $trimmed = ltrim($value);

            if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
                $decoded = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE && \is_array($decoded)) {
                    return self::deepDecode($decoded);
                }
            }
        }

        return $value;
    }
}
