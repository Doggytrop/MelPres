<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SoloAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->esAdmin()) {
            abort(403, 'No tienes permiso para acceder aquí.');
        }

        return $next($request);
    }
}