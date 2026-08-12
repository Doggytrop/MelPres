<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\SaasActivityLogIndexRequest;
use App\Models\SaasActivityLog;
use Illuminate\View\View;

class SaasActivityLogController extends Controller
{
    public function index(SaasActivityLogIndexRequest $request): View
    {
        $filters = $request->validated();

        $logs = SaasActivityLog::query()
            ->with(['actor', 'subject'])
            ->when(
                $filters['action'] ?? null,
                fn ($query, $action) => $query->where('action', $action)
            )
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $actions = [
            'company_created',
            'company_updated',
            'company_suspended',
            'company_reactivated',
            'company_marked_past_due',
            'company_cancelled',
            'company_renewed',
            'company_grace_updated',
            'company_grace_removed',
        ];

        return view('superadmin.activity-logs.index', compact('logs', 'actions', 'filters'));
    }
}
