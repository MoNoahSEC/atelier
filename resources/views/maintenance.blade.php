<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $settings['store_name'] ?? 'ATELIER' }} — Scheduled Archive Maintenance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background-color: #0A0A0A; }
    </style>
</head>
<body class="min-h-screen text-white flex flex-col justify-between p-6 sm:p-12">
    
    <!-- Top Header -->
    <header class="flex items-center justify-between border-b border-white/20 pb-6">
        <div class="font-black text-2xl tracking-tight uppercase">
            {{ $settings['store_name'] ?? 'ATELIER' }}
        </div>
        <span class="text-[10px] font-mono uppercase tracking-widest text-white/60 border border-white/30 px-3 py-1">
            MAINTENANCE PROTOCOL ACTIVE
        </span>
    </header>

    <!-- Main Message -->
    <main class="max-w-2xl my-auto py-12 space-y-6">
        <div class="inline-block bg-white text-black px-2.5 py-0.5 text-[10px] font-mono font-bold tracking-widest uppercase">
            SYSTEM CURATION
        </div>
        <h1 class="text-4xl sm:text-6xl font-black uppercase tracking-tight leading-none">
            Archival Updates in Progress.
        </h1>
        <p class="text-white/70 text-sm sm:text-base leading-relaxed">
            We are currently updating our catalog, server architecture, and private release reserves. Access will be restored shortly.
        </p>

        @if(!empty($settings['support_email']))
            <div class="pt-4 text-xs text-white/50">
                Direct Concierge Inquiry: <a href="mailto:{{ $settings['support_email'] }}" class="text-white underline font-mono">{{ $settings['support_email'] }}</a>
            </div>
        @endif
    </main>

    <!-- Footer -->
    <footer class="border-t border-white/20 pt-6 flex flex-col sm:flex-row items-center justify-between text-[11px] text-white/40 gap-4">
        <div>{{ $settings['footer_copyright'] ?? ('© ' . date('Y') . ' ATELIER. ALL RIGHTS RESERVED.') }}</div>
        <a href="{{ route('admin.login') }}" class="text-white/30 hover:text-white transition-colors uppercase font-mono text-[10px]">
            Administrative Portal →
        </a>
    </footer>

</body>
</html>
