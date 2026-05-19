<?php

namespace App\Modernization\Api;

use Illuminate\Support\Facades\Http;

final class ExternalIntegrationProbe
{
    /**
     * @return array{kind: string, ok: bool, status: int, retry: int, timeout: int}
     */
    public function probe(string $kind, string $url): array
    {
        $response = Http::timeout(2)
            ->connectTimeout(1)
            ->retry(2, 50)
            ->acceptJson()
            ->get($url);

        return [
            'kind' => $kind,
            'ok' => $response->successful(),
            'status' => $response->status(),
            'retry' => 2,
            'timeout' => 2,
        ];
    }
}
