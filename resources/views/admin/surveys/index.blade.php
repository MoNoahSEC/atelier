@extends('layouts.admin')

@section('title', 'Customer Surveys & Polls')

@section('content')
<div class="space-y-8" x-data="{ newSurveyModal: false }">

    <!-- Header & Quick Stats -->
    <div class="border-b-2 border-black pb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="text-2xl">📊</span>
                <h1 class="text-2xl font-black uppercase tracking-tight">Customer Surveys & Polls</h1>
            </div>
            <p class="text-xs text-gray-500 mt-1">
                استطلاعات الرأي واستبيانات رضا العملاء لمعرفة آراء المشترين وتحسين جودة المنتجات والتوصيل.
            </p>
        </div>

        <div>
            <button 
                type="button" 
                @click="newSurveyModal = true"
                class="bg-black text-white hover:bg-gray-800 border-2 border-black px-4 py-2 text-xs font-bold uppercase tracking-wider shadow-[3px_3px_0px_0px_rgba(0,0,0,1)] transition-transform active:translate-y-0.5 flex items-center gap-2"
            >
                <span>+</span>
                <span>إنشاء استطلاع جديد</span>
            </button>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white border-2 border-black p-4 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
            <span class="text-[10px] font-mono uppercase tracking-widest text-gray-500 block">Active Surveys</span>
            <span class="text-2xl font-black text-black font-mono block mt-1">{{ $totalSurveys }}</span>
            <span class="text-[10px] text-gray-400 mt-1 block">استطلاعات متاحة للعملاء</span>
        </div>

        <div class="bg-white border-2 border-black p-4 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
            <span class="text-[10px] font-mono uppercase tracking-widest text-gray-500 block">Total Customer Votes</span>
            <span class="text-2xl font-black text-black font-mono block mt-1">{{ number_format($totalResponses) }}</span>
            <span class="text-[10px] text-gray-400 mt-1 block">إجمالي ردود العملاء المسجلة</span>
        </div>

        <div class="bg-white border-2 border-black p-4 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
            <span class="text-[10px] font-mono uppercase tracking-widest text-gray-500 block">Average Experience Rating</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-black text-amber-500 font-mono">{{ $avgStoreRating ?: '5.0' }}</span>
                <span class="text-xs text-gray-500 font-mono">/ 5.0 ★</span>
            </div>
            <span class="text-[10px] text-gray-400 mt-1 block">متوسط رضا العملاء العام</span>
        </div>
    </div>

    <!-- Surveys List -->
    <div class="space-y-6">
        @forelse($surveys as $survey)
            @php $metrics = $survey->getMetrics(); @endphp
            <div class="bg-white border-2 border-black shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] p-6 space-y-6">
                
                <!-- Survey Header -->
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 border-b-2 border-black pb-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2.5">
                            <span class="text-xs font-mono font-bold uppercase px-2 py-0.5 border border-black {{ $survey->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                {{ $survey->is_active ? '● نشط ومفعل' : '○ متوقف مؤقتاً' }}
                            </span>
                            <span class="text-[10px] font-mono font-bold uppercase bg-black text-white px-2 py-0.5">
                                {{ strtoupper($survey->type) }}
                            </span>
                            <span class="text-[10px] font-mono text-gray-500">
                                Target: {{ $survey->target_page }}
                            </span>
                        </div>
                        <h2 class="text-lg font-black uppercase text-black pt-1">{{ $survey->title }}</h2>
                        <p class="text-xs font-bold text-gray-700">السؤال: {{ $survey->question }}</p>
                        @if($survey->description)
                            <p class="text-[11px] text-gray-500">{{ $survey->description }}</p>
                        @endif
                    </div>

                    <!-- Survey Actions -->
                    <div class="flex items-center gap-2 shrink-0">
                        <!-- Toggle Status -->
                        <form action="{{ route('admin.surveys.toggle', $survey->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="border border-black px-3 py-1.5 text-xs font-bold uppercase hover:bg-gray-100 transition-colors">
                                {{ $survey->is_active ? 'إيقاف مؤقت' : 'تفعيل' }}
                            </button>
                        </form>

                        <!-- Export CSV -->
                        <a href="{{ route('admin.surveys.export-csv', $survey->id) }}" class="border border-black bg-gray-50 hover:bg-black hover:text-white px-3 py-1.5 text-xs font-bold uppercase transition-colors">
                            📥 تصدير CSV
                        </a>

                        <!-- Delete -->
                        <form action="{{ route('admin.surveys.destroy', $survey->id) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الاستطلاع وجميع ردوده؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="border border-red-600 text-red-600 hover:bg-red-600 hover:text-white px-3 py-1.5 text-xs font-bold uppercase transition-colors">
                                حذف
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Live Results Visualization -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-gray-50 border border-black p-4">
                    <div class="border-b md:border-b-0 md:border-r border-black/20 pb-4 md:pb-0 md:pr-4">
                        <span class="text-[10px] font-bold uppercase text-gray-500 block">إجمالي المشاركات</span>
                        <span class="text-3xl font-black font-mono text-black">{{ $metrics['total_responses'] }}</span>
                        <span class="text-[10px] text-gray-400 block mt-1">صوت حتى الآن</span>
                    </div>

                    @if($survey->type === 'rating')
                        <div class="col-span-2 flex items-center gap-4">
                            <div>
                                <span class="text-[10px] font-bold uppercase text-gray-500 block">التقييم المتوسط</span>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-3xl font-black font-mono text-amber-500">{{ $metrics['average_rating'] ?: 'N/A' }}</span>
                                    <span class="text-sm font-mono text-gray-500">/ 5.0</span>
                                </div>
                            </div>
                            <div class="text-amber-400 text-xl tracking-widest">
                                @php $stars = (int)round($metrics['average_rating'] ?? 5); @endphp
                                @for($i=1; $i<=5; $i++)
                                    <span>{{ $i <= $stars ? '★' : '☆' }}</span>
                                @endfor
                            </div>
                        </div>
                    @elseif($survey->type === 'single_choice')
                        <div class="col-span-2 space-y-2">
                            <span class="text-[10px] font-bold uppercase text-gray-500 block">توزيع إجابات الخيارات</span>
                            @foreach($metrics['option_counts'] as $opt => $count)
                                @php 
                                    $pct = $metrics['total_responses'] > 0 ? round(($count / $metrics['total_responses']) * 100) : 0; 
                                @endphp
                                <div class="space-y-0.5">
                                    <div class="flex justify-between text-[11px] font-bold">
                                        <span class="truncate">{{ $opt }}</span>
                                        <span class="font-mono text-gray-600">{{ $count }} ({{ $pct }}%)</span>
                                    </div>
                                    <div class="w-full h-2 bg-gray-200 border border-black overflow-hidden">
                                        <div class="h-full bg-black transition-all duration-500" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="col-span-2 flex items-center">
                            <span class="text-xs text-gray-500 italic">استطلاع مفتوح لآراء ومقترحات العملاء المكتوبة. انظر أحدث الإجابات أدناه.</span>
                        </div>
                    @endif
                </div>

                <!-- Recent Responses Table -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-black uppercase tracking-wider text-black">أحدث إجابات العملاء (Latest Responses)</span>
                        <span class="text-[10px] text-gray-500 font-mono">{{ $survey->responses->count() }} مسجلة</span>
                    </div>

                    @if($survey->responses->isNotEmpty())
                        <div class="overflow-x-auto border border-black">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-black text-white text-[10px] uppercase tracking-wider font-mono">
                                        <th class="p-2 border-r border-white/20">التاريخ</th>
                                        <th class="p-2 border-r border-white/20">العميل</th>
                                        <th class="p-2 border-r border-white/20">التقييم / الاختيار</th>
                                        <th class="p-2 border-r border-white/20">التعليق والملاحظات</th>
                                        <th class="p-2 text-center">إجراء</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach($survey->responses as $resp)
                                        <tr class="hover:bg-gray-50">
                                            <td class="p-2 font-mono text-[10px] text-gray-500 whitespace-nowrap">{{ $resp->created_at->diffForHumans() }}</td>
                                            <td class="p-2 font-bold">{{ $resp->customer_name ?: 'عميل زائر' }}</td>
                                            <td class="p-2 font-mono">
                                                @if($resp->rating)
                                                    <span class="text-amber-600 font-bold">{{ $resp->rating }} / 5 ★</span>
                                                @elseif($resp->selected_option)
                                                    <span class="bg-gray-100 border border-black px-1.5 py-0.5 text-[10px] font-bold">{{ $resp->selected_option }}</span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="p-2 text-gray-700 italic max-w-md">{{ $resp->response_text ?: '—' }}</td>
                                            <td class="p-2 text-center">
                                                <form action="{{ route('admin.surveys.delete-response', $resp->id) }}" method="POST" class="inline" onsubmit="return confirm('حذف هذا الرد؟')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-black text-[10px] font-mono">✕ حذف</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-4 text-center text-xs text-gray-400 bg-gray-50 border border-dashed border-gray-300">
                            لا توجد ردود مسجلة لهذا الاستطلاع بعد.
                        </div>
                    @endif
                </div>

            </div>
        @empty
            <div class="bg-white border-2 border-black p-8 text-center shadow-[6px_6px_0px_0px_rgba(0,0,0,1)]">
                <span class="text-3xl block mb-2">📋</span>
                <h3 class="text-sm font-black uppercase">لا توجد استطلاعات رأي بعد</h3>
                <p class="text-xs text-gray-500 mt-1">اضغط على زر "إنشاء استطلاع جديد" للبدء في جمع آراء العملاء.</p>
            </div>
        @endforelse
    </div>

    <!-- Modal: Create New Survey -->
    <div 
        x-show="newSurveyModal" 
        x-cloak 
        class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4 backdrop-blur-sm"
    >
        <div 
            @click.away="newSurveyModal = false"
            class="bg-white border-2 border-black max-w-xl w-full p-6 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] max-h-[90vh] overflow-y-auto"
        >
            <div class="flex items-center justify-between border-b-2 border-black pb-3 mb-6">
                <h2 class="text-sm font-black uppercase tracking-wider">إنشاء استطلاع رأي عملاء جديد</h2>
                <button type="button" @click="newSurveyModal = false" class="text-black font-bold text-lg hover:opacity-70">✕</button>
            </div>

            <form action="{{ route('admin.surveys.store') }}" method="POST" class="space-y-4" x-data="{ surveyType: 'rating' }">
                @csrf

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-black mb-1">عنوان الاستطلاع (داخلي للإدارة) *</label>
                    <input type="text" name="title" required placeholder="مثال: قياس رضا العملاء عن التغليف الفاخر" class="w-full border-2 border-black p-2.5 text-xs bg-gray-50 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-black mb-1">سؤال الاستطلاع (المعروض للعميل) *</label>
                    <input type="text" name="question" required placeholder="مثال: كيف تقيم تجربتك الإجمالية وسرعة التوصيل؟" class="w-full border-2 border-black p-2.5 text-xs bg-gray-50 focus:bg-white focus:outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-black mb-1">نوع السؤال *</label>
                        <select name="type" x-model="surveyType" class="w-full border-2 border-black p-2.5 text-xs bg-gray-50 focus:bg-white focus:outline-none font-bold">
                            <option value="rating">تقييم بالنجوم (1 إلى 5 نجوم)</option>
                            <option value="single_choice">اختيار من متعدد (خيارات محددة)</option>
                            <option value="text">سؤال مفتوح (ملاحظات نصية)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-black mb-1">مكان الظهور المستهدف *</label>
                        <select name="target_page" class="w-full border-2 border-black p-2.5 text-xs bg-gray-50 focus:bg-white focus:outline-none font-bold">
                            <option value="order_confirmation">صفحة تأكيد الطلب (الأكثر فعالية)</option>
                            <option value="checkout">صفحة الدفع والشحن</option>
                            <option value="home">الصفحة الرئيسية</option>
                            <option value="all">جميع صفحات المتجر</option>
                        </select>
                    </div>
                </div>

                <!-- Single Choice Options Area -->
                <div x-show="surveyType === 'single_choice'" class="space-y-1">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-black mb-1">خيارات الإجابة (كل خيار في سطر جديد)</label>
                    <textarea name="options_raw" rows="4" placeholder="جودة الجلد الإيطالي&#10;سرعة التوصيل 24 ساعة&#10;فخامة بوكس التغليف&#10;سهولة الدفع الإلكتروني" class="w-full border-2 border-black p-2 text-xs font-mono bg-gray-50 focus:bg-white focus:outline-none"></textarea>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-black mb-1">وصف توضيحي أو سبب الاستطلاع (اختياري)</label>
                    <textarea name="description" rows="2" placeholder="تفاصيل داخلية عن أهداف هذا الاستبيان..." class="w-full border-2 border-black p-2 text-xs bg-gray-50 focus:bg-white focus:outline-none"></textarea>
                </div>

                <div class="pt-4 border-t border-black flex justify-end gap-3">
                    <button type="button" @click="newSurveyModal = false" class="border border-black px-4 py-2 text-xs font-bold uppercase hover:bg-gray-100">
                        إلغاء
                    </button>
                    <button type="submit" class="bg-black text-white hover:bg-gray-800 border-2 border-black px-6 py-2 text-xs font-bold uppercase tracking-wider shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                        حفظ ونشر الاستطلاع
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
