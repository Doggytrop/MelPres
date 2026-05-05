<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectCustomer
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->isCustomer()) {
            return redirect()->route('portal.index');
        }

        return $next($request);
    }
}