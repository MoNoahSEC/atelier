@props([
    'targetPage' => 'all',
    'orderId'    => null,
])

@php
    $activeSurvey = \App\Models\Survey::where('is_active', true)
        ->where(function($q) use ($targetPage) {
            $q->where('target_page', $targetPage)->orWhere('target_page', 'all');
        })
        ->orderBy('sort_order')
        ->first();
@endphp

@if($activeSurvey)
<div 
    x-data="{
        submitted: false,
        rating: 5,
        selectedOption: '',
        feedbackText: '',
        loading: false,
        submitSurvey() {
            this.loading = true;
            fetch('{{ route('api.surveys.respond', $activeSurvey->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    rating: '{{ $activeSurvey->type }}' === 'rating' ? this.rating : null,
                    selected_option: '{{ $activeSurvey->type }}' === 'single_choice' ? this.selectedOption : null,
                    response_text: this.feedbackText,
                    order_id: {{ $orderId ? (int)$orderId : 'null' }}
                })
            })
            .then(res => res.json())
            .then(data => {
                this.loading = false;
                this.submitted = true;
            })
            .catch(() => {
                this.loading = false;
                this.submitted = true;
            });
        }
    }"
    class="border-2 border-black bg-white p-5 sm:p-6 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] relative overflow-hidden"
>
    <!-- Before Submission -->
    <div x-show="!submitted" class="space-y-4">
        <div class="flex items-center justify-between border-b border-black/20 pb-2">
            <div class="flex items-center gap-2">
                <span class="text-base">📊</span>
                <span class="font-editorial font-bold text-xs uppercase tracking-wider text-black">
                    شاركنا رأيك في ثوانٍ (Client Voice)
                </span>
            </div>
            <span class="text-[9px] font-mono uppercase bg-black text-white px-2 py-0.5 font-bold">1-Click</span>
        </div>

        <div>
            <h4 class="font-editorial font-black text-sm uppercase text-black">
                {{ $activeSurvey->question }}
            </h4>
            @if($activeSurvey->description)
                <p class="text-[11px] text-gray-500 mt-0.5">{{ $activeSurvey->description }}</p>
            @endif
        </div>

        @if($activeSurvey->type === 'rating')
            <div class="flex items-center gap-2 py-1">
                <span class="text-xs font-bold text-gray-700 ml-2">تقييمك:</span>
                <div class="flex items-center gap-1.5 cursor-pointer">
                    @for($i = 1; $i <= 5; $i++)
                        <button 
                            type="button" 
                            @click="rating = {{ $i }}" 
                            class="text-2xl transition-transform hover:scale-125 focus:outline-none"
                            :class="rating >= {{ $i }} ? 'text-amber-500' : 'text-gray-300'"
                        >
                            ★
                        </button>
                    @endfor
                </div>
                <span class="text-xs font-mono font-bold text-black mr-2" x-text="rating + ' / 5'"></span>
            </div>
        @elseif($activeSurvey->type === 'single_choice' && !empty($activeSurvey->options_json))
            <div class="space-y-2 py-1">
                @foreach($activeSurvey->options_json as $opt)
                    <label class="flex items-center gap-2.5 p-2.5 border border-black cursor-pointer hover:bg-gray-50 transition-colors" :class="selectedOption === '{{ addslashes($opt) }}' ? 'bg-[#F5F5F0] border-2 font-bold' : ''">
                        <input type="radio" name="survey_opt_{{ $activeSurvey->id }}" value="{{ $opt }}" @click="selectedOption = '{{ addslashes($opt) }}'" class="accent-black">
                        <span class="text-xs text-black font-medium">{{ $opt }}</span>
                    </label>
                @endforeach
            </div>
        @endif

        <div>
            <input 
                type="text" 
                x-model="feedbackText" 
                placeholder="ملاحظاتك أو مقترحاتك الإضافية (اختياري)..."
                class="w-full border border-black p-2.5 text-xs bg-gray-50 focus:bg-white focus:outline-none"
            >
        </div>

        <div class="flex justify-end">
            <button 
                type="button" 
                @click="submitSurvey()"
                :disabled="loading || ('{{ $activeSurvey->type }}' === 'single_choice' && !selectedOption)"
                class="bg-black text-white hover:bg-gray-800 disabled:opacity-50 px-5 py-2 text-xs font-bold uppercase tracking-wider transition-colors shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:translate-y-0.5"
            >
                <span x-show="!loading">إرسال الرأي →</span>
                <span x-show="loading">جارٍ الإرسال...</span>
            </button>
        </div>
    </div>

    <!-- After Submission Success Message -->
    <div x-show="submitted" x-cloak class="py-6 text-center space-y-2">
        <span class="text-3xl block">✨</span>
        <h4 class="font-editorial font-black text-base uppercase text-black">
            شكراً جزيلاً لمشاركتك!
        </h4>
        <p class="text-xs text-gray-600 max-w-md mx-auto">
            تم تسجيل رأيك بنجاح. نقدر وقتك ونسعى دائماً لتطوير تجربة وتصميم منتجات ATELIER الفاخرة لتناسب ذوقك.
        </p>
    </div>
</div>
@endif
