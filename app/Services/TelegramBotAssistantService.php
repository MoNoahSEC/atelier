<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\TelegramRecipient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramBotAssistantService
{
    public function __construct(
        protected TelegramNotificationService $telegram
    ) {}

    /**
     * Send message with reply keyboard (persistent dashboard buttons at bottom of Telegram).
     */
    public function sendDashboard(string $chatId, ?string $customIntro = null): array
    {
        $storeName = Setting::get('store_name', 'ATELIER Studio');
        $token = $this->telegram->botToken();
        if (empty($token)) {
            return ['ok' => false, 'error' => 'Bot token not set'];
        }

        $text = $customIntro ?: "👑 <b>مرحباً بك في المساعد الذكي لمتجر {$storeName}</b> ⚡\n\n" .
            "أنا أتابع جميع عمليات المتجر الحية (طلبات، مبيعات، مخزون، وعملاء).\n" .
            "اضغط على أي زر في لوحة التحكم بالأسفل أو اكتب ما تبحث عنه:";

        $keyboard = [
            'keyboard' => [
                [
                    ['text' => '📊 إحصائيات المتجر'],
                    ['text' => '💰 مبيعات اليوم'],
                ],
                [
                    ['text' => '📦 أحدث الطلبات'],
                    ['text' => '⚠️ نواقص المخزون'],
                ],
                [
                    ['text' => '👥 تقرير العملاء'],
                    ['text' => '⚡ فحص النظام والروابط'],
                ],
            ],
            'resize_keyboard' => true,
            'persistent'      => true,
        ];

        return $this->rawSend($chatId, $text, $keyboard);
    }

    /**
     * Generate Comprehensive Real-Time Statistics Report.
     */
    public function getStatsReport(): string
    {
        $storeName = Setting::get('store_name', 'ATELIER Studio');
        $now = Carbon::now();
        $startOfDay = $now->copy()->startOfDay();
        $startOfMonth = $now->copy()->startOfMonth();

        // Orders metrics
        $totalOrders = Order::count();
        $todayOrders = Order::where('created_at', '>=', $startOfDay)->count();
        $monthOrders = Order::where('created_at', '>=', $startOfMonth)->count();

        // Revenue metrics (minor to EGP)
        $totalRevenue = (float) Order::where('payment_status', '!=', 'cancelled')
            ->sum('total_amount_minor') / 100;

        $todayRevenue = (float) Order::where('created_at', '>=', $startOfDay)
            ->where('payment_status', '!=', 'cancelled')
            ->sum('total_amount_minor') / 100;

        $monthRevenue = (float) Order::where('created_at', '>=', $startOfMonth)
            ->where('payment_status', '!=', 'cancelled')
            ->sum('total_amount_minor') / 100;

        // Status breakdown
        $pendingOrders = Order::whereIn('fulfillment_status', ['unfulfilled', 'pending'])->count();
        $deliveredOrders = Order::where('fulfillment_status', 'delivered')->count();

        // Catalog & customers
        $activeProducts = Product::where('status', 'active')->count();
        $lowStockCount = Product::where('status', 'active')->where('inventory', '<=', 5)->count();
        $totalCustomers = Customer::count();

        // Average order value
        $aov = $totalOrders > 0 ? ($totalRevenue / $totalOrders) : 0;

        return "📊 <b>تقرير إحصائيات متجر {$storeName}</b>\n" .
               "<i>بتوقيت: " . $now->format('Y-m-d h:i A') . "</i>\n" .
               "━━━━━━━━━━━━━━━━━━━━━\n\n" .
               "💵 <b>المبيعات والأرباح:</b>\n" .
               "• مبيعات اليوم: <b>" . number_format($todayRevenue, 0) . " EGP</b> (" . $todayOrders . " طلب)\n" .
               "• مبيعات هذا الشهر: <b>" . number_format($monthRevenue, 0) . " EGP</b> (" . $monthOrders . " طلب)\n" .
               "• إجمالي المبيعات الكلي: <b>" . number_format($totalRevenue, 0) . " EGP</b>\n" .
               "• متوسط قيمة الطلب (AOV): <b>" . number_format($aov, 0) . " EGP</b>\n\n" .
               "📦 <b>حالة الطلبات:</b>\n" .
               "• إجمالي كل الطلبات: <b>" . $totalOrders . "</b>\n" .
               "• طلبات قيد التجهيز/الشحن: <b>" . $pendingOrders . " ⏳</b>\n" .
               "• طلبات تم تسليمها بنجاح: <b>" . $deliveredOrders . " ✅</b>\n\n" .
               "🏷️ <b>المخزون والعملاء:</b>\n" .
               "• المنتجات المعروضة: <b>" . $activeProducts . " قطعة</b>\n" .
               "• تنبيهات نقص المخزون: <b>" . $lowStockCount . " منتج (≤5) ⚠️</b>\n" .
               "• قاعدة بيانات العملاء: <b>" . $totalCustomers . " عميل مسجل 👥</b>\n" .
               "━━━━━━━━━━━━━━━━━━━━━";
    }

    /**
     * Today's Sales Brief.
     */
    public function getTodaySalesReport(): string
    {
        $now = Carbon::now();
        $startOfDay = $now->copy()->startOfDay();

        $todayOrders = Order::with(['items'])->where('created_at', '>=', $startOfDay)->get();
        $todayRevenue = $todayOrders->where('payment_status', '!=', 'cancelled')->sum('total_amount_minor') / 100;
        $codCount = $todayOrders->where('payment_method', 'cod')->count();
        $onlineCount = $todayOrders->where('payment_method', '!=', 'cod')->count();

        $text = "💰 <b>ملخص مبيعات اليوم (" . $now->format('d M Y') . ")</b>\n" .
                "━━━━━━━━━━━━━━━━━━━━━\n" .
                "• إجمالي إيراد اليوم: <b>" . number_format($todayRevenue, 0) . " EGP</b>\n" .
                "• عدد الطلبات اليوم: <b>" . $todayOrders->count() . "</b>\n" .
                "• الدفع عند الاستلام (COD): <b>" . $codCount . "</b>\n" .
                "• دفع إلكتروني (Paymob / Visa): <b>" . $onlineCount . "</b>\n\n";

        if ($todayOrders->isNotEmpty()) {
            $text .= "<b>آخر طلبات اليوم:</b>\n";
            foreach ($todayOrders->take(5) as $ord) {
                $amt = number_format($ord->total_amount_minor / 100, 0);
                $text .= "• <code>#{$ord->order_number}</code> | {$amt} EGP | {$ord->customer_name}\n";
            }
        } else {
            $text .= "<i>لم يتم تسجيل أي طلبات جديدة حتى الآن اليوم.</i>\n";
        }

        $text .= "━━━━━━━━━━━━━━━━━━━━━";
        return $text;
    }

    /**
     * Recent Orders Report with Interactive Inline Buttons.
     */
    public function getRecentOrders(): array
    {
        $orders = Order::with(['customer'])->latest()->take(5)->get();

        if ($orders->isEmpty()) {
            return [
                'text' => "📦 <b>لا توجد طلبات مسجلة في المتجر حتى الآن.</b>",
                'buttons' => []
            ];
        }

        $text = "📦 <b>أحدث 5 طلبات مسجلة في المتجر:</b>\n━━━━━━━━━━━━━━━━━━━━━\n\n";
        $buttons = [];

        foreach ($orders as $i => $order) {
            $num = $i + 1;
            $amt = number_format($order->total_amount_minor / 100, 0);
            $customerName = $order->customer_name ?: ($order->customer ? trim($order->customer->first_name . ' ' . $order->customer->last_name) : ($order->metadata_json['shipping_address']['name'] ?? 'عميل المتجر'));
            $phone = $order->customer_phone ?: ($order->customer?->phone ?: ($order->metadata_json['shipping_address']['phone'] ?? '—'));
            $city = $order->metadata_json['shipping_address']['city'] ?? 'مصر';
            $statusEmoji = match($order->fulfillment_status) {
                'delivered' => '✅ تم التسليم',
                'shipped'   => '🚚 تم الشحن',
                'cancelled' => '❌ ملغي',
                default     => '⏳ جاري التجهيز',
            };

            $text .= "<b>{$num}. #{$order->order_number}</b> ({$statusEmoji})\n" .
                     "• العميل: <b>{$customerName}</b> ({$phone})\n" .
                     "• الوجهة: {$city} | القيمة: <b>{$amt} EGP</b>\n" .
                     "• التاريخ: " . $order->created_at->format('M d, h:i A') . "\n\n";

            // Add direct buttons
            $row = [
                ['text' => "🔍 طلب #{$order->order_number}", 'url' => url("/admin/orders/{$order->id}")],
            ];
            if (!empty($phone) && $phone !== '—') {
                $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                $row[] = ['text' => "💬 واتساب", 'url' => "https://wa.me/2{$cleanPhone}"];
            }
            $buttons[] = $row;
        }

        $text .= "━━━━━━━━━━━━━━━━━━━━━\n💡 يمكنك إرسال رقم أي طلب أو هاتف عميل في الشات للبحث عنه فوراً.";

        return [
            'text' => $text,
            'buttons' => $buttons,
        ];
    }

    /**
     * Low Stock Alert.
     */
    public function getLowStockReport(): array
    {
        $lowStock = Product::where('status', 'active')
            ->where('inventory', '<=', 5)
            ->orderBy('inventory', 'asc')
            ->take(8)
            ->get();

        if ($lowStock->isEmpty()) {
            return [
                'text' => "✅ <b>حالة المخزون ممتازة!</b>\nجميع المنتجات متوفرة بكميات كافية (أكثر من 5 قطع).",
                'buttons' => []
            ];
        }

        $text = "⚠️ <b>تنبيه نواقص المخزون (" . $lowStock->count() . " منتجات):</b>\n━━━━━━━━━━━━━━━━━━━━━\n\n";
        $buttons = [];

        foreach ($lowStock as $p) {
            $price = number_format(($p->retail_price_minor ?? 0) / 100, 0);
            $stockNotice = $p->inventory <= 0 ? "❌ نَفَد بالكامل (0)" : "⚠️ متبقي {$p->inventory} فقط";
            $text .= "• <b>{$p->title}</b>\n  {$stockNotice} | السعر: {$price} EGP\n\n";

            $buttons[] = [
                ['text' => "✎ تعديل مخزون {$p->title}", 'url' => url("/admin/quick-edit")]
            ];
        }

        $text .= "━━━━━━━━━━━━━━━━━━━━━";

        return [
            'text' => $text,
            'buttons' => $buttons,
        ];
    }

    /**
     * Customers & VIP Report.
     */
    public function getCustomersReport(): string
    {
        $totalCustomers = Customer::count();
        $totalUsers = User::count();

        // Top 3 customers by spending
        $topSpenders = Customer::withCount('orders')
            ->get()
            ->map(function ($c) {
                $spent = Order::where('customer_id', $c->id)->where('payment_status', '!=', 'cancelled')->sum('total_amount_minor') / 100;
                $c->total_spent = $spent;
                return $c;
            })
            ->sortByDesc('total_spent')
            ->take(3);

        $text = "👥 <b>تقرير العملاء وعملاء الـ VIP:</b>\n━━━━━━━━━━━━━━━━━━━━━\n\n" .
                "• إجمالي العملاء الذين اشتروا: <b>{$totalCustomers}</b>\n" .
                "• إجمالي الحسابات المسجلة بالموقع: <b>{$totalUsers}</b>\n\n" .
                "👑 <b>أعلى عملاء إنفاقاً (VIP):</b>\n";

        $rank = 1;
        foreach ($topSpenders as $top) {
            $spentFormatted = number_format($top->total_spent, 0);
            $text .= "{$rank}. <b>{$top->first_name} {$top->last_name}</b> ({$top->phone})\n" .
                     "   • عدد الطلبات: {$top->orders_count} | إجمالي: <b>{$spentFormatted} EGP</b>\n";
            $rank++;
        }

        $text .= "━━━━━━━━━━━━━━━━━━━━━";
        return $text;
    }

    /**
     * Search orders and customers by query string (order number, phone, name).
     */
    public function search(string $query): array
    {
        $clean = trim($query);
        $cleanPhone = preg_replace('/[^0-9]/', '', $clean);

        // 1. Search Orders
        $order = Order::with(['customer'])->where('order_number', 'like', "%{$clean}%")
            ->orWhere('customer_phone', 'like', "%{$cleanPhone}%")
            ->orWhere('customer_email', 'like', "%{$clean}%")
            ->orWhereHas('customer', function($q) use ($clean, $cleanPhone) {
                if (!empty($cleanPhone)) {
                    $q->where('phone', 'like', "%{$cleanPhone}%");
                }
                $q->orWhere('first_name', 'like', "%{$clean}%")
                  ->orWhere('last_name', 'like', "%{$clean}%");
            })
            ->latest()
            ->first();

        if ($order) {
            $amt = number_format($order->total_amount_minor / 100, 0);
            $shipping = $order->metadata_json['shipping_address'] ?? [];
            $customerName = $order->customer_name ?: ($order->customer ? trim($order->customer->first_name . ' ' . $order->customer->last_name) : ($shipping['name'] ?? 'عميل المتجر'));
            $phone = $order->customer_phone ?: ($order->customer?->phone ?: ($shipping['phone'] ?? '—'));
            $lat = $shipping['latitude'] ?? null;
            $lng = $shipping['longitude'] ?? null;

            $text = "🔍 <b>نتيجة البحث — طلب رقم #{$order->order_number}</b>\n━━━━━━━━━━━━━━━━━━━━━\n" .
                    "• العميل: <b>{$customerName}</b>\n" .
                    "• الهاتف: <code>{$phone}</code>\n" .
                    "• الإجمالي: <b>{$amt} EGP</b> ({$order->payment_method})\n" .
                    "• العنوان: {$shipping['street']}, {$shipping['city']}\n" .
                    "• الحالة: <b>{$order->fulfillment_status}</b>\n" .
                    "• التاريخ: " . $order->created_at->format('Y-m-d h:i A') . "\n" .
                    "━━━━━━━━━━━━━━━━━━━━━";

            $buttons = [];
            $row = [
                ['text' => '📦 فتح الطلب في الأدمن', 'url' => url("/admin/orders/{$order->id}")]
            ];
            if (!empty($order->customer_phone)) {
                $row[] = ['text' => '💬 واتساب', 'url' => "https://wa.me/2" . preg_replace('/[^0-9]/', '', $order->customer_phone)];
            }
            $buttons[] = $row;

            if ($lat && $lng) {
                $buttons[] = [
                    ['text' => '📍 موقع الخريطة الدقيق', 'url' => "https://maps.google.com/?q={$lat},{$lng}"]
                ];
            }

            return ['text' => $text, 'buttons' => $buttons];
        }

        return [
            'text' => "🔍 لم يتم العثور على أي نتائج مطابقة لـ «{$query}».\nيمكنك البحث برقم الطلب (مثل <code>AT-12345</code>) أو برقم هاتف العميل.",
            'buttons' => []
        ];
    }

    /**
     * Handle incoming webhook or polled update payload from Telegram.
     */
    public function handleIncomingUpdate(array $update): void
    {
        $message = $update['message'] ?? ($update['callback_query']['message'] ?? null);
        if (!$message) {
            return;
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        if (empty($chatId)) {
            return;
        }

        $text = trim($update['message']['text'] ?? ($update['callback_query']['data'] ?? ''));

        // Command routing
        if ($text === '/start' || $text === '/help' || $text === 'القائمة الرئيسية') {
            $this->sendDashboard($chatId);
            return;
        }

        if ($text === '📊 إحصائيات المتجر' || $text === '/stats') {
            $report = $this->getStatsReport();
            $this->telegram->sendMessageDetailed($chatId, $report);
            return;
        }

        if ($text === '💰 مبيعات اليوم' || $text === '/sales') {
            $report = $this->getTodaySalesReport();
            $this->telegram->sendMessageDetailed($chatId, $report);
            return;
        }

        if ($text === '📦 أحدث الطلبات' || $text === '/orders') {
            $data = $this->getRecentOrders();
            $this->telegram->sendMessageDetailed($chatId, $data['text'], $data['buttons']);
            return;
        }

        if ($text === '⚠️ نواقص المخزون' || $text === '/stock') {
            $data = $this->getLowStockReport();
            $this->telegram->sendMessageDetailed($chatId, $data['text'], $data['buttons']);
            return;
        }

        if ($text === '👥 تقرير العملاء' || $text === '/customers') {
            $report = $this->getCustomersReport();
            $this->telegram->sendMessageDetailed($chatId, $report);
            return;
        }

        if ($text === '⚡ فحص النظام والروابط' || $text === '/health') {
            $health = "⚡ <b>حالة النظام والروابط:</b>\n━━━━━━━━━━━━━━━━━━━━━\n" .
                      "• حالة البوت: <b>متصل ونشط 🟢</b>\n" .
                      "• رابط المتجر الحي: " . url('/') . "\n" .
                      "• لوحة الأدمن: " . url('/admin') . "\n" .
                      "• التوقيت الحالي: " . Carbon::now()->format('h:i A') . "\n" .
                      "━━━━━━━━━━━━━━━━━━━━━";
            $this->telegram->sendMessageDetailed($chatId, $health, [
                [
                    ['text' => '🛍️ تصفح المتجر', 'url' => url('/')],
                    ['text' => '⚙️ لوحة الأدمن', 'url' => url('/admin')]
                ]
            ]);
            return;
        }

        // Search trigger if text looks like an order number, phone number, or search command
        if (str_starts_with($text, '/search ') || preg_match('/^(\+?20|0)?1[0125][0-9]{8}$/', $text) || str_starts_with(strtoupper($text), 'AT-') || str_starts_with($text, '#')) {
            $searchQuery = str_replace('/search ', '', $text);
            $searchData = $this->search($searchQuery);
            $this->telegram->sendMessageDetailed($chatId, $searchData['text'], $searchData['buttons']);
            return;
        }

        // Default intelligent fallback
        $fallback = "🤖 <b>استلمت رسالتك: «{$text}»</b>\n\n" .
                    "يمكنك الضغط على الأزرار في الأسفل لطلب إحصائيات سريعة، أو كتابة رقم أي طلب أو هاتف عميل للبحث الفوري!";
        $this->sendDashboard($chatId, $fallback);
    }

    /**
     * Poll Telegram getUpdates on demand (works everywhere, even local localhost without domain).
     */
    public function pollUpdates(): array
    {
        $token = $this->telegram->botToken();
        if (empty($token)) {
            return ['success' => false, 'message' => 'Bot token is missing'];
        }

        try {
            $lastOffset = (int) Setting::get('telegram_last_update_offset', 0);
            $url = "https://api.telegram.org/bot{$token}/getUpdates?timeout=1";
            if ($lastOffset > 0) {
                $url .= "&offset={$lastOffset}";
            }

            $res = Http::withoutVerifying()->timeout(6)->get($url);
            $json = $res->json() ?? [];

            if (!$res->successful() || !($json['ok'] ?? false)) {
                return ['success' => false, 'message' => $json['description'] ?? 'Failed to get updates'];
            }

            $updates = $json['result'] ?? [];
            $processed = 0;

            foreach ($updates as $up) {
                $this->handleIncomingUpdate($up);
                $updateId = $up['update_id'] ?? null;
                if ($updateId) {
                    Setting::set('telegram_last_update_offset', (string) ($updateId + 1));
                }
                $processed++;
            }

            return ['success' => true, 'processed_count' => $processed];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Raw HTTP message sender with custom keyboard markup.
     */
    private function rawSend(string $chatId, string $text, array $replyMarkup): array
    {
        $token = $this->telegram->botToken();
        $params = [
            'chat_id'      => $chatId,
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => json_encode($replyMarkup, JSON_UNESCAPED_UNICODE),
        ];

        try {
            $res = Http::withoutVerifying()->timeout(8)->post("https://api.telegram.org/bot{$token}/sendMessage", $params);
            return $res->json() ?? ['ok' => false];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
