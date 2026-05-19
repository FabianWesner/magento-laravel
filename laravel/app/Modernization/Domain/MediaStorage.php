<?php

namespace App\Modernization\Domain;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class MediaStorage
{
    public function disk(string $disk = 'public'): Filesystem
    {
        return Storage::disk($disk);
    }

    public function putMedia(string $path, string $contents, string $disk = 'public'): string
    {
        $safePath = $this->assertSafePath($path);

        $this->disk($disk)->put($safePath, $contents);

        return $safePath;
    }

    /**
     * @return array{path: string, exists: bool, missing_media: bool, downloadable: bool}
     */
    public function inspect(string $path, string $disk = 'public'): array
    {
        $safePath = $this->assertSafePath($path);
        $exists = $this->disk($disk)->exists($safePath);

        return [
            'path' => $safePath,
            'exists' => $exists,
            'missing_media' => ! $exists,
            'downloadable' => str_starts_with($safePath, 'downloadable/'),
        ];
    }

    private function assertSafePath(string $path): string
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');

        if ($normalized === '' || str_contains($normalized, '../') || str_starts_with($normalized, '..')) {
            throw new InvalidArgumentException('Blocked media path traversal attempt.');
        }

        return $normalized;
    }
}
