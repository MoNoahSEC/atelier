@extends('layouts.admin')

@section('title', 'Team & Administrative Roles')

@section('content')
<div class="max-w-5xl space-y-8">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b-2 border-black pb-4">
        <div>
            <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500">ROLE-BASED ACCESS CONTROL</span>
            <h1 class="text-3xl font-black uppercase tracking-tight text-black">Admin Team & Permissions</h1>
            <p class="text-xs text-gray-500 mt-0.5">Manage administrative accounts and restrict access by team role (Super Admin, Manager, Staff)</p>
        </div>
    </div>

    <!-- Role Definitions Guide -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="border-2 border-black p-4 bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
            <span class="inline-block bg-black text-white text-[10px] font-bold uppercase px-2 py-0.5 mb-2">SUPER ADMIN</span>
            <p class="text-xs text-gray-700 font-medium leading-relaxed">
                Full unrestricted control across all financial analytics, site configuration, shipping/tax, and administrator accounts.
            </p>
        </div>
        <div class="border-2 border-black p-4 bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
            <span class="inline-block bg-purple-100 text-purple-900 border border-purple-400 text-[10px] font-bold uppercase px-2 py-0.5 mb-2">STORE MANAGER</span>
            <p class="text-xs text-gray-700 font-medium leading-relaxed">
                Full control over products, collections, inventory, orders, customer records, and promo coupons. Cannot modify team access.
            </p>
        </div>
        <div class="border-2 border-black p-4 bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
            <span class="inline-block bg-gray-100 text-gray-900 border border-gray-400 text-[10px] font-bold uppercase px-2 py-0.5 mb-2">STAFF / FULFILLMENT</span>
            <p class="text-xs text-gray-700 font-medium leading-relaxed">
                Can inspect and update order fulfillment statuses and view products. Cannot edit prices, delete records, or access team/shipping settings.
            </p>
        </div>
    </div>

    <!-- Create Admin User Card -->
    <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
        <h2 class="text-lg font-black uppercase tracking-tight text-black border-b-2 border-black pb-2">+ Create Administrator Account</h2>

        <form action="{{ route('admin.team.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Full Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Omar Farouk" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Email Address *</label>
                    <input type="email" name="email" required placeholder="omar@atelier.eg" class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Role *</label>
                    <select name="admin_role" required class="w-full border-2 border-black p-2.5 text-xs bg-white font-bold focus:outline-none">
                        <option value="super_admin">Super Admin (Full Access)</option>
                        <option value="manager" selected>Store Manager (Catalog & Orders)</option>
                        <option value="staff">Staff (Fulfillment Only)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Initial Password *</label>
                    <input type="password" name="password" required placeholder="Min. 8 characters" class="w-full border-2 border-black p-2.5 text-xs focus:outline-none">
                </div>
            </div>

            <button type="submit" class="bg-black text-white px-6 py-2.5 text-xs font-bold uppercase tracking-wider hover:bg-gray-800 shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]">
                Create Account →
            </button>
        </form>
    </div>

    <!-- Admins List Table -->
    <div class="bg-white border-2 border-black shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b-2 border-black bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-600">
                        <th class="p-3">Administrator</th>
                        <th class="p-3">Email</th>
                        <th class="p-3">Role</th>
                        <th class="p-3">Last Login</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 font-medium">
                    @foreach($admins as $admin)
                        @php
                            $role = $admin->admin_role ?: 'super_admin';
                            $isSelf = $admin->id === auth()->id();
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-3 font-bold text-black">
                                {{ $admin->name }}
                                @if($isSelf)
                                    <span class="ml-1 text-[9px] bg-black text-white px-1.5 py-0.2 uppercase font-mono">YOU</span>
                                @endif
                            </td>
                            <td class="p-3 font-mono text-gray-600">
                                {{ $admin->email }}
                            </td>
                            <td class="p-3">
                                <form action="{{ route('admin.team.update-role', $admin->id) }}" method="POST" class="inline-flex items-center gap-1">
                                    @csrf
                                    <select name="admin_role" onchange="this.form.submit()" class="border border-black p-1 text-[10px] font-bold uppercase bg-white cursor-pointer" {{ $isSelf ? 'disabled' : '' }}>
                                        <option value="super_admin" {{ $role === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                                        <option value="manager" {{ $role === 'manager' ? 'selected' : '' }}>Manager</option>
                                        <option value="staff" {{ $role === 'staff' ? 'selected' : '' }}>Staff</option>
                                    </select>
                                </form>
                            </td>
                            <td class="p-3 text-gray-500 font-mono text-[10px]">
                                {{ $admin->last_login_at ? $admin->last_login_at->diffForHumans() : 'Never' }}
                            </td>
                            <td class="p-3 text-right">
                                @if(!$isSelf)
                                    <form action="{{ route('admin.team.revoke', $admin->id) }}" method="POST" class="inline" onsubmit="return confirm('Revoke admin access for {{ addslashes($admin->email) }}?')">
                                        @csrf
                                        <button type="submit" class="border border-red-600 text-red-600 px-2 py-1 text-[10px] font-bold uppercase hover:bg-red-50">
                                            Revoke Access
                                        </button>
                                    </form>
                                @else
                                    <span class="text-gray-400 text-[10px] italic">Active Session</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
