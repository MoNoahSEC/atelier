<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        $admins = User::where('is_admin', true)->latest()->get();
        return view('admin.team.index', compact('admins'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|max:255|unique:users,email',
            'admin_role' => 'required|in:super_admin,manager,staff',
            'password'   => 'required|string|min:8',
        ]);

        $admin = User::create([
            'name'       => $request->input('name'),
            'email'      => strtolower(trim($request->input('email'))),
            'admin_role' => $request->input('admin_role'),
            'is_admin'   => true,
            'password'   => Hash::make($request->input('password')),
        ]);

        AuditLog::log('team.create', 'user', $admin->id, "Created new admin user {$admin->email} with role: {$admin->admin_role}");

        return back()->with('success', "Administrative user \"{$admin->name}\" created with role " . strtoupper($admin->admin_role));
    }

    public function updateRole(Request $request, int $id)
    {
        $request->validate([
            'admin_role' => 'required|in:super_admin,manager,staff',
        ]);

        $admin = User::where('is_admin', true)->findOrFail($id);

        // Prevent self-demotion if you're the currently logged in user
        if ($admin->id === auth()->id() && $request->input('admin_role') !== 'super_admin') {
            return back()->with('error', 'You cannot downgrade your own administrative role.');
        }

        $oldRole = $admin->admin_role;
        $admin->admin_role = $request->input('admin_role');
        $admin->save();

        AuditLog::log('team.role_change', 'user', $admin->id, "Changed role of {$admin->email} from {$oldRole} to {$admin->admin_role}");

        return back()->with('success', "Role for {$admin->email} updated to " . strtoupper($admin->admin_role));
    }

    public function revoke(int $id)
    {
        $admin = User::where('is_admin', true)->findOrFail($id);

        if ($admin->id === auth()->id()) {
            return back()->with('error', 'You cannot revoke your own administrator account.');
        }

        $admin->is_admin = false;
        $admin->save();

        AuditLog::log('team.revoke', 'user', $id, "Revoked admin privileges for {$admin->email}");

        return back()->with('success', "Administrative privileges revoked for {$admin->email}.");
    }
}
