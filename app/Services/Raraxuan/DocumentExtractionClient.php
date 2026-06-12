<?php

namespace App\Services\Raraxuan;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class DocumentExtractionClient
{
    /**
     * Send a document file to Raraxuan and return the decoded JSON response.
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    public function extract(
        string $template,
        array $variables,
        string $fileContents,
        string $filename,
        string $mimeType,
    ): array {
        $payload = ['template' => $template] + $this->flattenVariables($variables);

        return Http::withToken($this->apiKey())
            ->acceptJson()
            ->timeout($this->timeout())
            ->attach('file', $fileContents, $filename, ['Content-Type' => $mimeType])
            ->post($this->endpoint(), $payload)
            ->throw()
            ->json();
    }

    /**
     * Flatten the variables array into multipart field names (variables[key]).
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    private function flattenVariables(array $variables): array
    {
        $flattened = [];

        foreach ($variables as $key => $value) {
            $flattened["variables[{$key}]"] = $value;
        }

        return $flattened;
    }

    private function endpoint(): string
    {
        $baseUrl = config('raraxuan.base_url');

        if (! \is_string($baseUrl) || trim($baseUrl) === '') {
            throw new RuntimeException('Raraxuan base URL is not configured.');
        }

        $processPath = config('raraxuan.process_path', '/v1/prompts/process');

        if (! \is_string($processPath) || trim($processPath) === '') {
            throw new RuntimeException('Raraxuan process path is not configured.');
        }

        return rtrim($baseUrl, '/').'/'.ltrim(trim($processPath), '/');
    }

    private function apiKey(): string
    {
        $apiKey = config('raraxuan.api_key');

        if (! \is_string($apiKey) || trim($apiKey) === '') {
            throw new RuntimeException('Raraxuan API key is not configured.');
        }

        return trim($apiKey);
    }

    private function timeout(): int
    {
        $timeout = (int) config('raraxuan.timeout', 60);

        return $timeout > 0 ? $timeout : 60;
    }
}
