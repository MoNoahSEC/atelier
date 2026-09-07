<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AdminNotification::query()
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->has('is_read'), fn($q) => $q->where('is_read', filter_var($request->is_read, FILTER_VALIDATE_BOOLEAN)))
            ->orderByDesc('created_at');

        $notifications = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $notifications->items(),
            'meta'    => [
                'total'        => $notifications->total(),
                'per_page'     => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
            ],
        ]);
    }

    public function markAsRead(int $id): JsonResponse
    {
        $notification = AdminNotification::findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'data'    => $notification,
        ]);
    }

    public function markAllAsRead(): JsonResponse
    {
        AdminNotification::unread()->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        $count = AdminNotification::unread()->count();

        return response()->json([
            'success' => true,
            'data'    => ['count' => $count],
        ]);
    }
}
