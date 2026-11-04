<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tampilkan file bukti (privat) dengan aman.
 * Hanya gambar & PDF yang dibuka langsung di browser; jenis lain dipaksa unduh,
 * supaya file HTML/SVG yang disamarkan tidak bisa menjalankan script di sesi admin.
 */
trait ServesProofFiles
{
    private const INLINE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];

    protected function proofResponse(?string $path): StreamedResponse
    {
        $disk = Storage::disk('local');
        abort_unless($path && $disk->exists($path), 404);

        // Jenis file dibaca dari isinya, bukan dari ekstensi
        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        $inline = in_array($mime, self::INLINE_MIMES, true);

        $headers = [
            'Content-Type' => $inline ? $mime : 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ];

        return $disk->response($path, basename($path), $headers, $inline ? 'inline' : 'attachment');
    }
}
