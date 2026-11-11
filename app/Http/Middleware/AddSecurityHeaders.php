<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan standar untuk semua respons:
 * mencegah halaman dibingkai situs lain (clickjacking), tebakan jenis file oleh browser,
 * bocornya alamat lengkap ke situs lain, serta pengalihan form dan tag <base> ke luar situs.
 */
class AddSecurityHeaders
{
    private const HEADERS = [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
        'Content-Security-Policy' => "frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::HEADERS as $name => $value) {
            // Respons yang sudah menetapkan nilainya sendiri (mis. bukti bayar) tidak ditimpa
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        // Versi PHP tidak perlu diumumkan ke pengunjung
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        return $response;
    }
}
