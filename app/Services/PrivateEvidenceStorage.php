<?php

namespace App\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateEvidenceStorage
{
    public function store(UploadedFile $file, string $directory): string
    {
        $path = $file->store($directory, 'private');

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Berkas bukti gagal disimpan.');
        }

        return $path;
    }

    public function put(string $path, string $contents): void
    {
        if (! Storage::disk('private')->put($path, $contents)) {
            throw new RuntimeException('Berkas laporan gagal disimpan.');
        }
    }

    public function path(string $path): string
    {
        return Storage::disk('private')->path($path);
    }

    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        foreach (['private', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);

            if ($disk->exists($path) && ! $disk->delete($path)) {
                throw new RuntimeException('Berkas bukti gagal dihapus.');
            }
        }
    }

    public function response(string $path, bool $download = false): StreamedResponse
    {
        $disk = $this->diskContaining($path);

        abort_if(! $disk, 404);

        $headers = [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ];
        $filename = basename($path);

        return $download
            ? $disk->download($path, $filename, $headers)
            : $disk->response($path, $filename, $headers, 'inline');
    }

    private function diskContaining(string $path): ?FilesystemAdapter
    {
        foreach (['private', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);

            if ($disk->exists($path)) {
                return $disk;
            }
        }

        return null;
    }
}
