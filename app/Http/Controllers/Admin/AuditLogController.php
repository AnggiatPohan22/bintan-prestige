<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($request->user_id, fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->action, fn ($q) => $q->where('action', $request->action))
            ->when($request->type, fn ($q) => $q->where('auditable_type', $request->type))
            ->when($request->date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        $adminUsers = User::where('is_admin', true)->orderBy('name')->get(['id', 'name']);

        $actions = AuditLog::distinct()->orderBy('action')->pluck('action');

        $types = AuditLog::distinct()->orderBy('auditable_type')->pluck('auditable_type');

        return view('backend.audit-logs.index', [
            'logs'       => $logs,
            'adminUsers' => $adminUsers,
            'actions'    => $actions,
            'types'      => $types,
        ]);
    }
}
