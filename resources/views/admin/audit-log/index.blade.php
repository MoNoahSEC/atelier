@extends('layouts.admin')

@section('title', 'Security Audit Log')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b-2 border-black pb-4">
        <div>
            <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500">IMMUTABLE ACTIVITY RECORD</span>
            <h1 class="text-3xl font-black uppercase tracking-tight text-black">Security Audit Log</h1>
            <p class="text-xs text-gray-500 mt-0.5">Read-only chronological record of all administrative actions</p>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white border-2 border-black p-4 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
        <form method="GET" action="{{ route('admin.audit-log.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-600 mb-1">Filter by Action</label>
                <input type="text" name="action" value="{{ request('action') }}" placeholder="e.g. product.create, settings.update..." class="w-full border-2 border-black p-2 text-xs focus:outline-none font-mono">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-600 mb-1">Entity Type</label>
                <div class="flex items-center gap-2">
                    <select name="entity_type" class="w-full border-2 border-black p-2 text-xs bg-white focus:outline-none">
                        <option value="">All Entities</option>
                        <option value="product" {{ request('entity_type') === 'product' ? 'selected' : '' }}>Products</option>
                        <option value="order" {{ request('entity_type') === 'order' ? 'selected' : '' }}>Orders</option>
                        <option value="coupon" {{ request('entity_type') === 'coupon' ? 'selected' : '' }}>Coupons</option>
                        <option value="setting" {{ request('entity_type') === 'setting' ? 'selected' : '' }}>Settings</option>
                        <option value="collection" {{ request('entity_type') === 'collection' ? 'selected' : '' }}>Collections</option>
                        <option value="review" {{ request('entity_type') === 'review' ? 'selected' : '' }}>Reviews</option>
                        <option value="user" {{ request('entity_type') === 'user' ? 'selected' : '' }}>Users / Customers</option>
                    </select>
                    <button type="submit" class="bg-black text-white px-4 py-2 text-xs font-bold uppercase">
                        Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Audit Log Entries -->
    <div class="bg-white border-2 border-black shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] overflow-hidden">
        @if($logs->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b-2 border-black bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-600">
                            <th class="p-3 font-mono">#</th>
                            <th class="p-3">Timestamp</th>
                            <th class="p-3">Action</th>
                            <th class="p-3">Entity</th>
                            <th class="p-3">Admin IP</th>
                            <th class="p-3">Summary</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 font-medium">
                        @foreach($logs as $log)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="p-3 font-mono text-gray-400 text-[10px]">{{ $log->id }}</td>
                                <td class="p-3 font-mono text-gray-600 text-[10px]">
                                    {{ $log->created_at->format('M d, Y') }}
                                    <br>{{ $log->created_at->format('H:i:s') }}
                                </td>
                                <td class="p-3">
                                    <span class="font-mono font-bold text-[10px] bg-gray-100 border border-gray-300 px-1.5 py-0.5">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <span class="font-bold text-black">{{ $log->entity_type }}</span>
                                    @if($log->entity_id)
                                        <span class="text-gray-400 font-mono"> #{{ $log->entity_id }}</span>
                                    @endif
                                </td>
                                <td class="p-3 font-mono text-[10px] text-gray-500">
                                    {{ $log->ip_address ?? '—' }}
                                </td>
                                <td class="p-3 text-gray-700 max-w-xs">
                                    @if(is_array($log->changes_json) && isset($log->changes_json['summary']))
                                        <span class="text-[11px]">{{ $log->changes_json['summary'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t-2 border-black bg-gray-50">
                {{ $logs->links() }}
            </div>
        @else
            <div class="p-12 text-center text-gray-500">
                <span class="text-4xl block mb-2">🛡️</span>
                <h3 class="font-bold text-sm uppercase tracking-wider">No Audit Entries Yet</h3>
                <p class="text-xs text-gray-400 mt-1">Administrative actions will be automatically recorded here as you use the panel.</p>
            </div>
        @endif
    </div>

</div>
@endsection
