<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('audit_logs')
            ->when($request->action, fn($q) => $q->where('action', $request->action))
            ->when($request->entity_type, fn($q) => $q->where('entity_type', $request->entity_type))
            ->when($request->date_from, fn($q) => $q->where('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->where('created_at', '<=', $request->date_to))
            ->orderByDesc('created_at');

        $logs = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $logs->items(),
            'meta'    => [
                'total'        => $logs->total(),
                'per_page'     => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
            ],
        ]);
    }
}
