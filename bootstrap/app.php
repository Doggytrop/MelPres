<?php

use App\Http\Middleware\SetCompanyContext;
use App\Http\Middleware\RequireCompanyContext;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureSubscriptionAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetCompanyContext::class,
        ]);

        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            SetCompanyContext::class,
        );

        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            RequireCompanyContext::class,
        );

        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            EnsureSubscriptionAccess::class,
        );

        $middleware->alias([
            'solo.admin'        => \App\Http\Middleware\SoloAdmin::class,
            'solo.collector'    => \App\Http\Middleware\SoloCollector::class,
            'redirect.customer' => \App\Http\Middleware\RedirectCustomer::class,
            'company.required'  => RequireCompanyContext::class,
            'superadmin'        => EnsureSuperAdmin::class,
            'subscription.access' => EnsureSubscriptionAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
