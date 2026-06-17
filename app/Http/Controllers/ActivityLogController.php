<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::latest();

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user')) {
            $query->where('user_id', $request->user);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(25);

        $modules = ActivityLog::distinct('module')->pluck('module');
        $users = ActivityLog::distinct('user_id')
                            ->whereNotNull('user_id')
                            ->with('user')
                            ->get()
                            ->pluck('user')
                            ->filter()
                            ->unique('id');

        return view('activity-logs.index', compact('logs', 'modules', 'users'));
    }
}