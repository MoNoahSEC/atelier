@extends('layouts.admin')

@section('title', $customer->name . ' — Customer Profile')

@section('content')
<div class="max-w-5xl space-y-8">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b-2 border-black pb-4">
        <div>
            <a href="{{ route('admin.customers.index') }}" class="text-xs font-bold uppercase tracking-wider text-gray-500 hover:text-black mb-1 block">
                ← Back to Customers
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-black uppercase tracking-tight text-black">{{ $customer->name }}</h1>
                @if($customer->is_banned)
                    <span class="text-xs font-bold uppercase px-2.5 py-0.5 border bg-red-100 text-red-800 border-red-600">BLOCKED</span>
                @else
                    <span class="text-xs font-bold uppercase px-2.5 py-0.5 border bg-green-100 text-green-800 border-green-600">ACTIVE</span>
                @endif
            </div>
            <p class="text-xs text-gray-500 mt-1 font-mono">{{ $customer->email }} · Joined {{ $customer->created_at->format('M d, Y') }}</p>
        </div>

        <div class="flex items-center gap-3">
            <!-- Secure password reset (NO plaintext passwords) -->
            <form action="{{ route('admin.customers.send-reset', $customer->id) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="border border-black bg-white px-4 py-2 text-xs font-bold uppercase tracking-wider hover:bg-gray-100" title="Send a secure password reset link — no plaintext password is ever shown or stored.">
                    🔑 Send Reset Link
                </button>
            </form>

            <form action="{{ route('admin.customers.toggle-ban', $customer->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ $customer->is_banned ? 'Unblock' : 'Block' }} this account?')">
                @csrf
                <button type="submit" class="{{ $customer->is_banned ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }} text-white px-4 py-2 text-xs font-bold uppercase tracking-wider border border-black">
                    {{ $customer->is_banned ? 'Unblock Account' : 'Block Account' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white border-2 border-black p-4 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] text-center">
            <div class="text-2xl font-black font-mono text-black">{{ $ordersCount }}</div>
            <div class="text-[10px] font-bold uppercase tracking-wider text-gray-500 mt-1">Total Orders</div>
        </div>
        <div class="bg-white border-2 border-black p-4 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] text-center">
            <div class="text-2xl font-black font-mono text-black">{{ number_format($totalSpent / 100, 0) }}</div>
            <div class="text-[10px] font-bold uppercase tracking-wider text-gray-500 mt-1">EGP Spent</div>
        </div>
        <div class="bg-white border-2 border-black p-4 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] text-center">
            <div class="text-2xl font-black font-mono text-black">{{ $customer->addresses->count() }}</div>
            <div class="text-[10px] font-bold uppercase tracking-wider text-gray-500 mt-1">Saved Addresses</div>
        </div>
        <div class="bg-white border-2 border-black p-4 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] text-center">
            <div class="text-sm font-black text-black leading-tight">
                {{ $customer->last_login_at ? $customer->last_login_at->diffForHumans() : 'Never' }}
            </div>
            <div class="text-[10px] font-bold uppercase tracking-wider text-gray-500 mt-1">Last Session</div>
        </div>
    </div>

    <!-- Orders History -->
    <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
        <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 border-b pb-2">
            Order History ({{ $orders->count() }})
        </h2>

        @if($orders->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($orders as $order)
                    @php
                        $badge = match($order->status) {
                            'delivered'  => 'bg-green-100 text-green-800 border-green-600',
                            'shipped'    => 'bg-blue-100 text-blue-800 border-blue-600',
                            'processing' => 'bg-purple-100 text-purple-800 border-purple-600',
                            'cancelled'  => 'bg-red-100 text-red-800 border-red-600',
                            default      => 'bg-amber-100 text-amber-800 border-amber-600',
                        };
                    @endphp
                    <div class="py-3 flex items-center justify-between gap-3">
                        <div>
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="font-black text-sm font-mono text-black hover:underline">
                                #{{ $order->order_number }}
                            </a>
                            <span class="text-xs text-gray-500 ml-2">{{ $order->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-mono font-black text-black text-sm">{{ number_format($order->total_price_minor / 100, 0) }} EGP</span>
                            <span class="text-[10px] font-bold uppercase border px-2 py-0.5 {{ $badge }}">{{ $order->status }}</span>
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="border border-black px-2 py-1 text-[10px] font-bold uppercase hover:bg-black hover:text-white">→</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-xs text-gray-400 py-4 text-center">No orders placed by this customer yet.</p>
        @endif
    </div>

    <!-- Saved Addresses -->
    @if($customer->addresses->count() > 0)
        <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] space-y-4">
            <h2 class="text-sm font-mono font-bold uppercase tracking-widest text-gray-500 border-b pb-2">
                Saved Delivery Addresses ({{ $customer->addresses->count() }})
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($customer->addresses as $addr)
                    <div class="border border-black p-3 text-xs space-y-1">
                        <div class="font-black text-black uppercase">{{ $addr->full_name ?? $customer->name }}</div>
                        <div class="text-gray-700">{{ $addr->street_address ?? $addr->address_line_1 }}</div>
                        @if(isset($addr->apartment) && $addr->apartment) <div class="text-gray-600">{{ $addr->apartment }}</div> @endif
                        <div class="text-gray-700">{{ $addr->city }} {{ isset($addr->governorate) ? '· ' . $addr->governorate : '' }}</div>
                        @if($addr->phone ?? false) <div class="font-mono text-gray-500">{{ $addr->phone }}</div> @endif
                        @if($addr->is_default ?? false)
                            <span class="inline-block bg-black text-white text-[9px] font-bold uppercase px-1.5 py-0.5">DEFAULT</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Security Note (No plaintext password, ever) -->
    <div class="bg-gray-50 border border-gray-300 p-4 text-xs text-gray-600">
        <strong>Security Notice:</strong> Passwords are hashed with bcrypt and are never visible to administrators. Use "Send Reset Link" to trigger a secure token-based password reset for this customer.
        @if($customer->password_reset_requested_at)
            <br><span class="text-amber-700 font-bold">Last reset was requested: {{ $customer->password_reset_requested_at->format('M d, Y H:i') }}</span>
        @endif
    </div>

</div>
@endsection
