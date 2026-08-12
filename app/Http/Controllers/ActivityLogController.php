<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\CompanyContext;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request, CompanyContext $companyContext)
    {
        $company = $companyContext->getCompany();

        if (! $company || $company->status !== 'active') {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        $companyId = $company->id;

        $query = ActivityLog::where('activity_logs.company_id', $companyId)
            ->latest('activity_logs.created_at');

        if ($request->filled('module')) {
            $query->where('activity_logs.module', $request->module);
        }

        if ($request->filled('action')) {
            $query->where('activity_logs.action', $request->action);
        }

        if ($request->filled('user')) {
            $query->where('activity_logs.user_id', $request->user);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($query) use ($search) {
                $query->where('activity_logs.description', 'like', "%{$search}%")
                    ->orWhere('activity_logs.user_name', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('activity_logs.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('activity_logs.created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(25);

        $modules = ActivityLog::where('activity_logs.company_id', $companyId)
            ->distinct()
            ->pluck('module');
        $userIds = ActivityLog::where('activity_logs.company_id', $companyId)
            ->distinct()
            ->whereNotNull('user_id')
            ->pluck('user_id');
        $users = User::where('users.company_id', $companyId)
            ->whereIn('users.id', $userIds)
            ->get();

        return view('activity-logs.index', compact('logs', 'modules', 'users'));
    }
}
