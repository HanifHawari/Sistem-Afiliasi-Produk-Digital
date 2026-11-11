<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Route per area, masing-masing dipegang satu anggota tim
            Route::middleware('web')->group(base_path('routes/shop.php'));
            Route::middleware(['web', 'auth', 'role:admin'])
                ->prefix('admin')->name('admin.')
                ->group(base_path('routes/admin.php'));
            Route::middleware(['web', 'auth', 'role:affiliate'])
                ->prefix('affiliate')->name('affiliate.')
                ->group(base_path('routes/affiliate.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Header keamanan untuk semua respons (anti-clickjacking, nosniff, dst.)
        $middleware->append(AddSecurityHeaders::class);

        // Akun yang dinonaktifkan admin langsung dikeluarkan di halaman mana pun
        $middleware->web(append: [EnsureAccountIsActive::class]);

        // Ganti atau reset password memutus sesi yang masih terbuka di perangkat lain
        $middleware->authenticateSessions();

        // Di luar lingkungan local, hanya host milik APP_URL (dan subdomainnya) yang dilayani.
        // Mencegah Host header palsu, misalnya untuk membelokkan link reset password.
        $middleware->trustHosts();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
