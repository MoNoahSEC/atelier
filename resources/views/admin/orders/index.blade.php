@extends('layouts.admin')

@section('title', 'Orders Management')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b-2 border-black pb-4">
        <div>
            <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500">FULFILLMENT & TRANSACTIONS</span>
            <h1 class="text-3xl font-black uppercase tracking-tight text-black">Customer Orders</h1>
            <p class="text-xs text-gray-500 mt-0.5">{{ $orders->total() }} total transactions recorded</p>
        </div>
    </div>

    <!-- Status Tabs / Pills -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b-2 border-black">
        @php
            $currentStatus = request('status', '');
        @endphp
        <a href="{{ route('admin.orders.index') }}" class="px-3 py-1.5 text-xs font-bold uppercase tracking-wider border {{ empty($currentStatus) ? 'bg-black text-white border-black' : 'bg-white text-gray-700 border-gray-300 hover:border-black' }} whitespace-nowrap">
            All ({{ $counts['all'] }})
        </a>
        <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="px-3 py-1.5 text-xs font-bold uppercase tracking-wider border {{ $currentStatus === 'pending' ? 'bg-black text-white border-black' : 'bg-white text-amber-800 border-amber-300 hover:border-black' }} whitespace-nowrap">
            Pending ({{ $counts['pending'] }})
        </a>
        <a href="{{ route('admin.orders.index', ['status' => 'processing']) }}" class="px-3 py-1.5 text-xs font-bold uppercase tracking-wider border {{ $currentStatus === 'processing' ? 'bg-black text-white border-black' : 'bg-white text-purple-800 border-purple-300 hover:border-black' }} whitespace-nowrap">
            Processing ({{ $counts['processing'] }})
        </a>
        <a href="{{ route('admin.orders.index', ['status' => 'shipped']) }}" class="px-3 py-1.5 text-xs font-bold uppercase tracking-wider border {{ $currentStatus === 'shipped' ? 'bg-black text-white border-black' : 'bg-white text-blue-800 border-blue-300 hover:border-black' }} whitespace-nowrap">
            Shipped ({{ $counts['shipped'] }})
        </a>
        <a href="{{ route('admin.orders.index', ['status' => 'delivered']) }}" class="px-3 py-1.5 text-xs font-bold uppercase tracking-wider border {{ $currentStatus === 'delivered' ? 'bg-black text-white border-black' : 'bg-white text-green-800 border-green-300 hover:border-black' }} whitespace-nowrap">
            Delivered ({{ $counts['delivered'] }})
        </a>
        <a href="{{ route('admin.orders.index', ['status' => 'cancelled']) }}" class="px-3 py-1.5 text-xs font-bold uppercase tracking-wider border {{ $currentStatus === 'cancelled' ? 'bg-black text-white border-black' : 'bg-white text-red-800 border-red-300 hover:border-black' }} whitespace-nowrap">
            Cancelled ({{ $counts['cancelled'] }})
        </a>
    </div>

    <!-- Search & Date Filter -->
    <div class="bg-white border-2 border-black p-4 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            <div class="sm:col-span-2">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-600 mb-1">Search Customer or Order #</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Order #, name, email, phone..." class="w-full border-2 border-black p-2 text-xs focus:outline-none">
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-600 mb-1">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full border-2 border-black p-2 text-xs bg-white focus:outline-none font-mono">
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-600 mb-1">Date To</label>
                <div class="flex items-center gap-2">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full border-2 border-black p-2 text-xs bg-white focus:outline-none font-mono">
                    <button type="submit" class="bg-black text-white px-4 py-2 text-xs font-bold uppercase hover:bg-gray-800">
                        Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="bg-white border-2 border-black shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] overflow-hidden">
        @if($orders->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b-2 border-black bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-600">
                            <th class="p-3">Order #</th>
                            <th class="p-3">Customer</th>
                            <th class="p-3">Placed On</th>
                            <th class="p-3">Payment</th>
                            <th class="p-3">Items</th>
                            <th class="p-3">Total Amount</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 font-medium">
                        @foreach($orders as $order)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="p-3 font-mono font-bold text-black">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="hover:underline">
                                        #{{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="p-3">
                                    <div class="font-bold text-black">{{ $order->customer_name ?: 'Guest' }}</div>
                                    <div class="text-[10px] text-gray-500 font-mono">{{ $order->customer_email }}</div>
                                    @if($order->customer_phone)
                                        <div class="text-[10px] text-gray-400 font-mono">{{ $order->customer_phone }}</div>
                                    @endif
                                </td>
                                <td class="p-3 text-gray-600">
                                    {{ $order->created_at->format('M d, Y') }}
                                    <span class="block text-[10px] text-gray-400 font-mono">{{ $order->created_at->format('H:i') }}</span>
                                </td>
                                <td class="p-3">
                                    <span class="text-[10px] font-bold uppercase bg-gray-100 border border-gray-300 px-1.5 py-0.5">
                                        {{ $order->payment_method ?? 'COD' }}
                                    </span>
                                    <span class="block text-[9px] text-gray-500 mt-0.5 capitalize">{{ $order->payment_status ?? 'pending' }}</span>
                                </td>
                                <td class="p-3">
                                    <span class="font-bold">{{ $order->items->sum('quantity') }}</span> item(s)
                                </td>
                                <td class="p-3 font-mono font-black text-sm text-black">
                                    {{ number_format($order->total_price_minor / 100, 0) }} EGP
                                </td>
                                <td class="p-3">
                                    @php
                                        $badgeClass = match($order->status) {
                                            'delivered'  => 'bg-green-100 text-green-800 border-green-600',
                                            'shipped'    => 'bg-blue-100 text-blue-800 border-blue-600',
                                            'processing' => 'bg-purple-100 text-purple-800 border-purple-600',
                                            'cancelled'  => 'bg-red-100 text-red-800 border-red-600',
                                            default      => 'bg-amber-100 text-amber-800 border-amber-600',
                                        };
                                    @endphp
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider border {{ $badgeClass }}">
                                        {{ $order->status }}
                                    </span>
                                </td>
                                <td class="p-3 text-right">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="bg-black text-white px-3 py-1 text-[10px] font-bold uppercase hover:bg-gray-800 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                                        Inspect →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t-2 border-black bg-gray-50 flex items-center justify-between">
                {{ $orders->links() }}
            </div>
        @else
            <div class="p-12 text-center text-gray-500">
                <span class="text-4xl block mb-2">📦</span>
                <h3 class="font-bold text-sm uppercase tracking-wider">No Orders Match Query</h3>
                <p class="text-xs text-gray-400 mt-1">Try resetting the status or date filters.</p>
            </div>
        @endif
    </div>

</div>
@endsection
