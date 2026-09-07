@extends('layouts.admin')

@section('title', 'Customer Management')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b-2 border-black pb-4">
        <div>
            <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500">CLIENTELE & AUDIENCE</span>
            <h1 class="text-3xl font-black uppercase tracking-tight text-black">Registered Customers</h1>
            <p class="text-xs text-gray-500 mt-0.5">{{ $customers->total() }} customer accounts registered</p>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white border-2 border-black p-4 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
        <form method="GET" action="{{ route('admin.customers.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-600 mb-1">Search Customer Name or Email</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email..." class="w-full border-2 border-black p-2 text-xs focus:outline-none">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-600 mb-1">Status</label>
                <div class="flex items-center gap-2">
                    <select name="status" class="w-full border-2 border-black p-2 text-xs bg-white focus:outline-none">
                        <option value="">All Customers</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Accounts</option>
                        <option value="banned" {{ request('status') === 'banned' ? 'selected' : '' }}>Blocked Accounts</option>
                    </select>
                    <button type="submit" class="bg-black text-white px-4 py-2 text-xs font-bold uppercase hover:bg-gray-800">
                        Search
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Customers Table -->
    <div class="bg-white border-2 border-black shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] overflow-hidden">
        @if($customers->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b-2 border-black bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-600">
                            <th class="p-3">Customer Name</th>
                            <th class="p-3">Email Address</th>
                            <th class="p-3">Joined Date</th>
                            <th class="p-3">Last Login</th>
                            <th class="p-3">Orders</th>
                            <th class="p-3">Total Spent</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 font-medium">
                        @foreach($customers as $customer)
                            @php
                                $stat = $orderStats->get($customer->email);
                                $ordersCount = $stat ? $stat->orders_count : 0;
                                $totalSpent = $stat ? $stat->total_spent : 0;
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="p-3 font-bold text-black">
                                    <a href="{{ route('admin.customers.show', $customer->id) }}" class="hover:underline">
                                        {{ $customer->name }}
                                    </a>
                                </td>
                                <td class="p-3 font-mono text-gray-600">
                                    {{ $customer->email }}
                                </td>
                                <td class="p-3 text-gray-600">
                                    {{ $customer->created_at->format('M d, Y') }}
                                </td>
                                <td class="p-3 text-gray-500 font-mono text-[11px]">
                                    @if($customer->last_login_at)
                                        {{ $customer->last_login_at->diffForHumans() }}
                                    @else
                                        <span class="text-gray-400">Never</span>
                                    @endif
                                </td>
                                <td class="p-3 font-mono">
                                    <span class="font-bold text-black">{{ $ordersCount }}</span> orders
                                </td>
                                <td class="p-3 font-mono font-black text-black">
                                    {{ number_format($totalSpent / 100, 0) }} EGP
                                </td>
                                <td class="p-3">
                                    @if($customer->is_banned)
                                        <span class="inline-block px-2 py-0.5 text-[9px] font-bold uppercase bg-red-100 text-red-800 border border-red-600">
                                            Blocked
                                        </span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 text-[9px] font-bold uppercase bg-green-100 text-green-800 border border-green-600">
                                            Active
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 text-right space-x-1">
                                    <a href="{{ route('admin.customers.show', $customer->id) }}" class="bg-black text-white px-2.5 py-1 text-[10px] font-bold uppercase hover:bg-gray-800">
                                        View Profile →
                                    </a>
                                    <form action="{{ route('admin.customers.toggle-ban', $customer->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ $customer->is_banned ? 'Unblock' : 'Block' }} {{ addslashes($customer->email) }}?')">
                                        @csrf
                                        <button type="submit" class="border border-black px-2 py-1 text-[10px] font-bold uppercase hover:bg-gray-100">
                                            {{ $customer->is_banned ? 'Unblock' : 'Block' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t-2 border-black bg-gray-50 flex items-center justify-between">
                {{ $customers->links() }}
            </div>
        @else
            <div class="p-12 text-center text-gray-500">
                <span class="text-4xl block mb-2">👥</span>
                <h3 class="font-bold text-sm uppercase tracking-wider">No Customers Found</h3>
            </div>
        @endif
    </div>

</div>
@endsection
