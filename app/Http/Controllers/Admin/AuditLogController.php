<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::latest();

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->input('action')}%");
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        $logs = $query->paginate(30)->withQueryString();

        return view('admin.audit-log.index', compact('logs'));
    }
}
