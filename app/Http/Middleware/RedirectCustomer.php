<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectCustomer
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            if (auth()->user()->isCustomer()) {
                return redirect()->route('portal.index');
            }
            if (auth()->user()->isCollector()) {
                return redirect()->route('collector.index');
            }
        }

        return $next($request);
    }
}