<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\SaasActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function index(): View
    {
        $summary = [
            'total' => Company::query()->count(),
            'active' => Company::query()
                ->whereHas('subscription', fn (Builder $query) => $query->effectivelyActive())
                ->count(),
            'past_due' => Company::query()
                ->whereHas('subscription', fn (Builder $query) => $query->effectivelyPastDue())
                ->count(),
            'suspended' => Company::query()
                ->whereHas('subscription', fn (Builder $query) => $query->where('status', 'suspended'))
                ->count(),
            'renewals_soon' => Company::query()
                ->whereHas('subscription', function (Builder $query): void {
                    $query->effectivelyActive()
                        ->whereBetween('current_period_end', [now(), now()->addDays(30)]);
                })
                ->count(),
        ];

        $recentLogs = SaasActivityLog::query()
            ->with(['actor', 'subject'])
            ->latest('id')
            ->limit(8)
            ->get();

        return view('superadmin.dashboard', compact('summary', 'recentLogs'));
    }
}
