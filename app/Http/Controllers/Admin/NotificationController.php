<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = AdminNotification::latest()->paginate(25);
        $unreadCount = AdminNotification::where('is_read', false)->count();

        return view('admin.notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markAllAsRead()
    {
        AdminNotification::where('is_read', false)->update(['is_read' => true]);
        return back()->with('success', 'All notifications marked as read.');
    }

    public function markAsRead(int $id)
    {
        $notification = AdminNotification::findOrFail($id);
        $notification->is_read = true;
        $notification->save();
        return back()->with('success', 'Notification marked as read.');
    }

    public function destroy(int $id)
    {
        $notification = AdminNotification::findOrFail($id);
        $notification->delete();
        return back()->with('success', 'Notification dismissed.');
    }
}
