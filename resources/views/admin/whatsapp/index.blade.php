@extends('layouts.admin')

@section('title', 'WhatsApp Client Notifications')

@section('content')
<div class="space-y-8" x-data="{ showToken: false }">

    <!-- Header & Status -->
    <div class="border-b-2 border-black pb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="text-2xl">💬</span>
                <h1 class="text-2xl font-black uppercase tracking-tight">WhatsApp Client Notifications</h1>
            </div>
            <p class="text-xs text-gray-500 mt-1">
                إرسال إشعارات فورية وتأكيدات الشحن وتحديثات الطلبات تلقائياً لأرقام هواتف العملاء عبر واتساب.
            </p>
        </div>

        <!-- Connection Status Badge -->
        <div>
            @if($connectionStatus['ok'])
                <div class="inline-flex items-center gap-2 bg-green-500 text-white text-xs font-mono font-bold px-3 py-1.5 border-2 border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]">
                    <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                    <span>CONNECTED & ACTIVE</span>
                </div>
            @else
                <div class="inline-flex items-center gap-2 bg-gray-100 text-gray-700 text-xs font-mono font-bold px-3 py-1.5 border-2 border-black shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>STATUS: {{ $connectionStatus['status'] }}</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Main Grid: Settings & Test Sender -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Configuration Form (2 cols) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)]">
                <div class="flex items-center justify-between border-b-2 border-black pb-3 mb-6">
                    <h2 class="text-sm font-black uppercase tracking-wider">1. بيانات الربط والبوابة (Gateway Credentials)</h2>
                    <span class="text-[10px] bg-black text-white px-2 py-0.5 font-bold uppercase">Settings</span>
                </div>

                <form action="{{ route('admin.whatsapp.settings') }}" method="POST" class="space-y-5">
                    @csrf

                    <!-- Enable / Disable Toggle -->
                    <div class="bg-gray-50 border border-black p-4 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-black uppercase tracking-wider block">تفعيل إرسال رسائل واتساب للعملاء</span>
                            <span class="text-[11px] text-gray-500 block mt-0.5">عند تفعيل هذا الخيار، سيتم إرسال إشعار تأكيد فوري برقم الطلب والتفاصيل بمجرد إتمام العميل للشراء.</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="whatsapp_enabled" value="1" {{ $enabled ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none border-2 border-black peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-black after:border after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                        </label>
                    </div>

                    <!-- Provider Selector -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            بوابة الإرسال (Provider) *
                        </label>
                        <select name="whatsapp_provider" class="w-full border-2 border-black p-2.5 text-xs font-bold focus:outline-none bg-white">
                            <option value="ultramsg" {{ $provider === 'ultramsg' ? 'selected' : '' }}>UltraMsg (الأسهل والأسرع - يدعم مسح QR Code في دقيقة)</option>
                            <option value="generic" {{ $provider === 'generic' ? 'selected' : '' }}>Generic WhatsApp HTTP API / Wasapi</option>
                        </select>
                    </div>

                    <!-- Instance ID -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            معرّف الخدمة (Instance ID) *
                        </label>
                        <input 
                            type="text" 
                            name="whatsapp_instance_id" 
                            value="{{ old('whatsapp_instance_id', $instanceId) }}" 
                            placeholder="مثال: instance99234" 
                            class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none bg-white"
                        >
                        <span class="text-[10px] text-gray-500 block mt-1">تجد هذا المعرف في لوحة تحكم حسابك في UltraMsg فور إنشاء الـ Instance.</span>
                    </div>

                    <!-- API Token -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            رمز المصادقة (API Token) *
                        </label>
                        <div class="flex gap-2">
                            <input 
                                :type="showToken ? 'text' : 'password'" 
                                name="whatsapp_api_token" 
                                value="{{ old('whatsapp_api_token', $apiToken) }}" 
                                placeholder="مثال: p7m2k9a1z4b..." 
                                class="flex-1 border-2 border-black p-2.5 text-xs font-mono focus:outline-none bg-white"
                            >
                            <button 
                                type="button" 
                                @click="showToken = !showToken" 
                                class="border-2 border-black px-3 text-xs font-bold uppercase bg-gray-100 hover:bg-gray-200"
                            >
                                <span x-text="showToken ? 'Hide' : 'Show'"></span>
                            </button>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="bg-black text-white px-6 py-3 text-xs font-bold uppercase tracking-wider hover:bg-gray-800 transition-colors shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]">
                            💾 حفظ إعدادات واتساب
                        </button>
                    </div>
                </form>
            </div>

            <!-- Visual Arabic Guide for 1-minute Setup -->
            <div class="bg-[#F5F5F0] border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)]">
                <div class="flex items-center gap-2 border-b-2 border-black pb-2 mb-4">
                    <span class="text-lg">📖</span>
                    <h3 class="text-xs font-black uppercase tracking-wider">شرح كيفية الربط في دقيقة واحدة (خطوة بخطوة)</h3>
                </div>

                <div class="space-y-4 text-xs font-sans leading-relaxed text-gray-800">
                    <div class="flex items-start gap-3 bg-white p-3 border border-black">
                        <span class="font-mono font-bold bg-black text-white w-6 h-6 flex items-center justify-center shrink-0">1</span>
                        <div>
                            <p class="font-bold">افتح موقع UltraMsg وأنشئ حساباً مجانياً:</p>
                            <p class="text-gray-600 mt-0.5">ادخل على الرابط <a href="https://ultramsg.com" target="_blank" class="text-black font-bold underline">ultramsg.com</a> واضغط "Try Free" ثم اختر إنشاء Instance جديدة.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 bg-white p-3 border border-black">
                        <span class="font-mono font-bold bg-black text-white w-6 h-6 flex items-center justify-center shrink-0">2</span>
                        <div>
                            <p class="font-bold">امسح رمز الـ QR بكاميرا واتساب من هاتفك:</p>
                            <p class="text-gray-600 mt-0.5">سيظهر لك رمز QR في الشاشة، افتح تطبيق واتساب بهاتفك > الأجهزة المرتبطة (Linked Devices) > امسح الرمز لربط رقم واتساب المتجر.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 bg-white p-3 border border-black">
                        <span class="font-mono font-bold bg-black text-white w-6 h-6 flex items-center justify-center shrink-0">3</span>
                        <div>
                            <p class="font-bold">انسخ الـ Instance ID و Token والصقهما هنا:</p>
                            <p class="text-gray-600 mt-0.5">انسخ المعرفين والصقهما في الخانات في الأعلى، ثم اضغط على "حفظ إعدادات واتساب".</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 bg-white p-3 border border-black">
                        <span class="font-mono font-bold bg-black text-white w-6 h-6 flex items-center justify-center shrink-0">4</span>
                        <div>
                            <p class="font-bold">جرّب إرسال رسالة تيست:</p>
                            <p class="text-gray-600 mt-0.5">أدخل رقم هاتفك في الخانة المجاورة واضغط "إرسال رسالة تجريبية" لتتأكد من وصول الرسالة فوراً لهاتفك!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Test Message Sender & Sample Template (1 col) -->
        <div class="space-y-6">
            
            <!-- Live Test Message Sender -->
            <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)]">
                <div class="flex items-center justify-between border-b-2 border-black pb-3 mb-4">
                    <h3 class="text-xs font-black uppercase tracking-wider">2. تجربة الإرسال الحي (Live Test)</h3>
                    <span class="text-xs">🧪</span>
                </div>

                <p class="text-[11px] text-gray-600 mb-4 leading-relaxed">
                    أدخل أي رقم هاتف محمول (مصري أو دولي) لإرسال رسالة اختبارية والتأكد من عمل البوابة.
                </p>

                <form action="{{ route('admin.whatsapp.test') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            رقم الهاتف للتجربة *
                        </label>
                        <input 
                            type="text" 
                            name="test_phone" 
                            placeholder="01050417732" 
                            required 
                            class="w-full border-2 border-black p-2.5 text-xs font-mono focus:outline-none bg-gray-50 focus:bg-white"
                        >
                        <span class="text-[10px] text-gray-400 block mt-1">يمكنك كتابته كـ 010... أو 2010...</span>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            نص رسالة التجربة (اختياري)
                        </label>
                        <textarea 
                            name="test_message" 
                            rows="3" 
                            placeholder="رسالة تجريبية من لوحة تحكم ATELIER..."
                            class="w-full border-2 border-black p-2 text-xs focus:outline-none bg-gray-50 focus:bg-white"
                        ></textarea>
                    </div>

                    <button type="submit" class="w-full bg-green-600 text-white hover:bg-green-700 border-2 border-black py-2.5 text-xs font-bold uppercase tracking-wider transition-colors shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                        🚀 إرسال رسالة تجريبية الآن
                    </button>
                </form>
            </div>

            <!-- Message Preview Template -->
            <div class="bg-white border-2 border-black p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)]">
                <div class="flex items-center justify-between border-b-2 border-black pb-2 mb-3">
                    <h3 class="text-xs font-black uppercase tracking-wider">نموذج الرسالة التلقائية للعميل</h3>
                    <span class="text-[9px] bg-green-100 text-green-800 border border-green-700 px-1.5 py-0.5 font-bold">Auto</span>
                </div>

                <div class="bg-gray-100 border border-black p-3 text-[11px] font-mono text-gray-800 space-y-2 whitespace-pre-line leading-relaxed">
مرحباً [اسم العميل] 👑

شكراً لتسوقك من *ATELIER Studio Egypt* الفاخرة! ✨
تم استلام وتأكيد طلبك بنجاح رقم:
*#ORD-XXXXXX*

📦 *القطع المختارة:*
• ATELIER Slim MagSafe Leather Wallet × 1 (540.00 EGP)

💵 *الإجمالي:* 590.00 EGP
💳 *طريقة السداد:* الدفع عند الاستلام (COD)
📍 *عنوان التوصيل:* 14 Brazil St, Zamalek, Cairo
📍 *موقع الخريطة:* https://maps.google.com/?q=30.05,31.22

🚚 *تتبع الشحنة:*
https://atelier.eg/track-order?order=ORD-XXXXXX
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
