<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Redirect user ke halaman ganti password jika must_change_password = true.
     * Kecuali jika sudah di halaman ganti password atau logout.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->user() &&
            $request->user()->must_change_password &&
            ! $request->routeIs('password.force-change') &&
            ! $request->routeIs('password.force-change.update') &&
            ! $request->routeIs('logout')
        ) {
            return redirect()->route('password.force-change');
        }

        return $next($request);
    }
}
