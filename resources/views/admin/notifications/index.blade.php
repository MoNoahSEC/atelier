@extends('layouts.admin')

@section('title', 'System Notifications')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b-2 border-black pb-4">
        <div>
            <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500">SYSTEM ALERTS</span>
            <h1 class="text-3xl font-black uppercase tracking-tight text-black">Notifications</h1>
            @if($unreadCount > 0)
                <p class="text-xs text-amber-700 font-bold mt-0.5">{{ $unreadCount }} unread notification(s)</p>
            @else
                <p class="text-xs text-gray-500 mt-0.5">All caught up!</p>
            @endif
        </div>

        @if($unreadCount > 0)
            <form action="{{ route('admin.notifications.mark-all-read') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="border border-black bg-white px-4 py-2 text-xs font-bold uppercase tracking-wider hover:bg-black hover:text-white transition-colors">
                    ✓ Mark All As Read
                </button>
            </form>
        @endif
    </div>

    <!-- Notifications List -->
    @if($notifications->count() > 0)
        <div class="space-y-3">
            @foreach($notifications as $notification)
                <div class="bg-white border-2 {{ !$notification->is_read ? 'border-black shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]' : 'border-gray-200' }} p-5 flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3 flex-1">
                        <!-- Unread dot -->
                        @if(!$notification->is_read)
                            <div class="w-2 h-2 rounded-full bg-black mt-1 shrink-0 animate-pulse"></div>
                        @else
                            <div class="w-2 h-2 mt-1 shrink-0"></div>
                        @endif

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <span class="text-[10px] font-bold uppercase px-1.5 py-0.2 bg-gray-100 border border-gray-300">
                                    {{ $notification->type ?? 'system' }}
                                </span>
                                <span class="text-[10px] text-gray-400 font-mono">{{ $notification->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm font-bold text-black">{{ $notification->title ?? 'System Notification' }}</p>
                            @if($notification->message)
                                <p class="text-xs text-gray-600 mt-0.5">{{ $notification->message }}</p>
                            @elseif(is_array($notification->data_json) && isset($notification->data_json['message']))
                                <p class="text-xs text-gray-600 mt-0.5">{{ $notification->data_json['message'] }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        @if(!$notification->is_read)
                            <form action="{{ route('admin.notifications.mark-read', $notification->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="border border-black px-2.5 py-1 text-[10px] font-bold uppercase hover:bg-black hover:text-white transition-colors">
                                    Mark Read
                                </button>
                            </form>
                        @endif
                        <form action="{{ route('admin.notifications.destroy', $notification->id) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-600 text-xs font-bold" title="Dismiss">✕</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex justify-center">
            {{ $notifications->links() }}
        </div>
    @else
        <div class="py-16 text-center text-gray-400 bg-white border-2 border-black">
            <span class="text-4xl block mb-2">🔔</span>
            <h3 class="font-bold text-sm uppercase tracking-wider">No system notifications</h3>
            <p class="text-[10px] text-gray-400 mt-1">All clear — new orders, low stock alerts, and system events will appear here.</p>
        </div>
    @endif

</div>
@endsection
