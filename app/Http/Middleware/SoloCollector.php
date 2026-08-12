<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SoloCollector
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->role !== 'collector') {
            abort(403, 'No tienes permiso para acceder al módulo de cobrador.');
        }

        return $next($request);
    }
}
