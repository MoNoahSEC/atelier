@extends('layouts.app')

@section('title', 'Google OAuth Setup Guide — ' . ($settings['storeName'] ?? 'ATELIER'))

@section('content')
<div class="bg-[#F5F5F0] min-h-screen py-12 lg:py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Top Card --}}
        <div class="border-2 border-black bg-white p-8 sm:p-12 shadow-[10px_10px_0px_0px_rgba(0,0,0,1)] space-y-8">
            
            <div class="border-b-2 border-black pb-6 flex items-start justify-between flex-wrap gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 border border-black px-2.5 py-0.5 bg-black text-white text-[9px] font-editorial font-bold uppercase tracking-widest mb-2">
                        <span>🔐 GOOGLE AUTHENTICATION</span>
                    </div>
                    <h1 class="font-display text-2xl sm:text-4xl uppercase tracking-tight text-black">
                        ربط تسجيل الدخول بجوجل (Google Sign-In)
                    </h1>
                    <p class="font-sans text-xs sm:text-sm text-black/60 mt-1.5">
                        خطوات تفعيل زر «تسجيل الدخول عبر Google» لعملائك بخطوات رسمية ومجانية من Google Cloud.
                    </p>
                </div>
                <a href="{{ route('account') }}" class="text-xs font-editorial font-bold uppercase tracking-wider text-black underline">
                    ← العودة لصفحة الحساب
                </a>
            </div>

            {{-- Live URLs Box --}}
            <div class="border-2 border-black bg-[#F5F5F0] p-5 space-y-3">
                <p class="font-editorial font-bold text-xs uppercase tracking-wider text-black">
                    📌 الروابط المطلوبة في إعدادات Google Console:
                </p>
                <div class="space-y-2 font-mono text-xs">
                    <div>
                        <span class="text-black/60 block text-[10px] uppercase font-sans font-bold">Authorized JavaScript Origins (رابط موقعك):</span>
                        <div class="flex items-center justify-between bg-white border border-black p-2 mt-1">
                            <span class="select-all font-bold text-black">{{ $siteUrl }}</span>
                            <button onclick="navigator.clipboard.writeText('{{ $siteUrl }}'); alert('تم نسخ رابط الموقع!');" class="text-[10px] font-sans font-bold uppercase bg-black text-white px-2 py-1">نسخ</button>
                        </div>
                    </div>
                    <div>
                        <span class="text-black/60 block text-[10px] uppercase font-sans font-bold">Authorized Redirect URI (رابط إعادة التوجيه Callback):</span>
                        <div class="flex items-center justify-between bg-white border border-black p-2 mt-1">
                            <span class="select-all font-bold text-black">{{ $callbackUrl }}</span>
                            <button onclick="navigator.clipboard.writeText('{{ $callbackUrl }}'); alert('تم نسخ رابط الـ Callback!');" class="text-[10px] font-sans font-bold uppercase bg-black text-white px-2 py-1">نسخ</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step by Step Guide --}}
            <div class="space-y-6">
                <h2 class="font-editorial font-black text-lg uppercase tracking-wider text-black border-b border-black pb-2">
                    خطوات التفعيل خطوة بخطوة (تستغرق 3 دقائق فقط)
                </h2>

                <div class="space-y-4 font-sans text-xs sm:text-sm text-black/85 leading-relaxed">
                    
                    {{-- Step 1 --}}
                    <div class="border border-black/20 p-4 bg-white flex gap-4 items-start">
                        <span class="w-7 h-7 bg-black text-white font-editorial font-bold flex items-center justify-center shrink-0 text-xs">01</span>
                        <div class="space-y-1">
                            <h3 class="font-editorial font-bold text-xs uppercase tracking-wider text-black">فتح وحدة تحكم Google Cloud Console</h3>
                            <p class="text-xs text-black/70">
                                توجه إلى <a href="https://console.cloud.google.com/" target="_blank" class="text-black font-bold underline">console.cloud.google.com</a> وسجل دخول بحساب جوجل الخاص بك.
                            </p>
                        </div>
                    </div>

                    {{-- Step 2 --}}
                    <div class="border border-black/20 p-4 bg-white flex gap-4 items-start">
                        <span class="w-7 h-7 bg-black text-white font-editorial font-bold flex items-center justify-center shrink-0 text-xs">02</span>
                        <div class="space-y-1">
                            <h3 class="font-editorial font-bold text-xs uppercase tracking-wider text-black">إنشاء مشروع جديد (Create New Project)</h3>
                            <p class="text-xs text-black/70">
                                اضغط على قائمة المشاريع في الأعلى واضغط <b>New Project</b>، سمه مثلاً <b>ATELIER Store</b> ثم اضغط <b>Create</b>.
                            </p>
                        </div>
                    </div>

                    {{-- Step 3 --}}
                    <div class="border border-black/20 p-4 bg-white flex gap-4 items-start">
                        <span class="w-7 h-7 bg-black text-white font-editorial font-bold flex items-center justify-center shrink-0 text-xs">03</span>
                        <div class="space-y-1">
                            <h3 class="font-editorial font-bold text-xs uppercase tracking-wider text-black">إعداد شاشة الموافقة (OAuth Consent Screen)</h3>
                            <p class="text-xs text-black/70">
                                من القائمة الجانبية اختر <b>APIs & Services → OAuth consent screen</b>. اختر <b>External</b> ثم أدخل اسم المتجر وبريدك الإلكتروني واضغط حفظ ومتابعة.
                            </p>
                        </div>
                    </div>

                    {{-- Step 4 --}}
                    <div class="border border-black/20 p-4 bg-white flex gap-4 items-start">
                        <span class="w-7 h-7 bg-black text-white font-editorial font-bold flex items-center justify-center shrink-0 text-xs">04</span>
                        <div class="space-y-1">
                            <h3 class="font-editorial font-bold text-xs uppercase tracking-wider text-black">إنشاء مفاتيح الاعتماد (Credentials)</h3>
                            <p class="text-xs text-black/70">
                                من القائمة الجانبية اختر <b>Credentials → Create Credentials → OAuth Client ID</b>.
                            </p>
                            <ul class="list-disc list-inside text-xs text-black/70 pt-1 space-y-1">
                                <li>Application Type: اختر <b>Web application</b>.</li>
                                <li>Name: اسميها مثلاً <b>ATELIER Web Client</b>.</li>
                                <li><b>Authorized JavaScript origins:</b> الصق رابط موقعك الموضح أعلاه.</li>
                                <li><b>Authorized redirect URIs:</b> الصق رابط الـ Callback الموضح أعلاه.</li>
                            </ul>
                        </div>
                    </div>

                    {{-- Step 5 --}}
                    <div class="border border-black/20 p-4 bg-white flex gap-4 items-start">
                        <span class="w-7 h-7 bg-black text-white font-editorial font-bold flex items-center justify-center shrink-0 text-xs">05</span>
                        <div class="space-y-1">
                            <h3 class="font-editorial font-bold text-xs uppercase tracking-wider text-black">نسخ المفاتيح إلى ملف .env</h3>
                            <p class="text-xs text-black/70">
                                بعد الحفظ، ستعطيك جوجل <b>Client ID</b> و <b>Client Secret</b>. افتح ملف <code>.env</code> في مشروعك وضع القيمتين في الأسفل:
                            </p>
                            <div class="bg-black text-white p-3 font-mono text-xs mt-2 border border-black space-y-1" dir="ltr">
                                <div>GOOGLE_CLIENT_ID=xxxxxxxxxx.apps.googleusercontent.com</div>
                                <div>GOOGLE_CLIENT_SECRET=GOCSPX-xxxxxxxxxxxxxxxxxxxx</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Final Note --}}
            <div class="border-t-2 border-black pt-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                <span class="text-xs text-black/60 font-sans">بمجرد حفظ المتغيرين في ملف .env سيعمل تسجيل الدخول بجوجل مباشرة دون أي إعداد إضافي!</span>
                <a href="{{ route('account') }}" class="btn-luxury px-6 py-3 text-xs">
                    فهمت، العودة للمتجر ←
                </a>
            </div>

        </div>

    </div>
</div>
@endsection
