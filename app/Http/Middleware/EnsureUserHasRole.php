<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pemakaian di route: ->middleware('role:admin') atau 'role:admin,affiliate'
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        // Akun nonaktif sudah dikeluarkan lebih dulu oleh EnsureAccountIsActive (grup web)
        abort_unless(in_array($user->role->value, $roles, true), 403);

        return $next($request);
    }
}
