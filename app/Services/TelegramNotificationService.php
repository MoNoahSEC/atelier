<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\TelegramRecipient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramNotificationService
{
    public function botToken(): ?string
    {
        $raw = Setting::get('telegram_bot_token');
        if (empty($raw)) {
            return null;
        }

        // Clean any leading/trailing whitespace, quotes, or accidental 'bot' prefix
        $token = trim((string)$raw);
        $token = preg_replace('#^https?://api\.telegram\.org/bot#i', '', $token);
        $token = preg_replace('#^bot#i', '', $token);
        $token = trim($token, " \t\n\r\0\x0B'\"");

        return !empty($token) ? $token : null;
    }

    private function apiUrl(string $method): string
    {
        return "https://api.telegram.org/bot{$this->botToken()}/{$method}";
    }

    /**
     * Test the bot token using Telegram's getMe method.
     * Returns array with status and bot details or error.
     */
    public function verifyBot(): array
    {
        $token = $this->botToken();
        if (empty($token)) {
            return ['ok' => false, 'error' => 'No Bot Token configured.'];
        }

        try {
            $response = Http::withoutVerifying()->timeout(8)->get($this->apiUrl('getMe'));
            $data = $response->json();

            if ($response->successful() && ($data['ok'] ?? false)) {
                return [
                    'ok'       => true,
                    'bot_id'   => $data['result']['id'] ?? null,
                    'name'     => $data['result']['first_name'] ?? 'Bot',
                    'username' => $data['result']['username'] ?? null,
                ];
            }

            $desc = $data['description'] ?? 'Unauthorized or invalid token.';
            return ['ok' => false, 'error' => $desc];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Network / Connection error: ' . $e->getMessage()];
        }
    }

    /**
     * Send a message to a single chat ID with optional inline keyboard buttons.
     */
    public function sendMessageDetailed(string $chatId, string $text, ?array $inlineKeyboard = null): array
    {
        $token = $this->botToken();
        if (empty($token)) {
            return ['success' => false, 'error' => 'Bot Token is not set.'];
        }

        $cleanChatId = trim(filter_var($chatId, FILTER_SANITIZE_NUMBER_INT));
        if (empty($cleanChatId)) {
            $cleanChatId = trim($chatId);
        }

        $params = [
            'chat_id'    => $cleanChatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ];

        if (!empty($inlineKeyboard)) {
            $params['reply_markup'] = json_encode(['inline_keyboard' => $inlineKeyboard], JSON_UNESCAPED_UNICODE);
        }

        try {
            $response = Http::withoutVerifying()->timeout(8)->post($this->apiUrl('sendMessage'), $params);

            $json = $response->json() ?? [];

            if ($response->successful() && ($json['ok'] ?? false)) {
                return ['success' => true, 'error' => null];
            }

            $desc = $json['description'] ?? ('HTTP ' . $response->status() . ': ' . $response->body());

            // Provide clear actionable guidance for common Telegram API errors
            if (str_contains(strtolower($desc), 'chat not found')) {
                $desc .= " — (Action required: You must search for your bot in Telegram and press START / send /start before Telegram allows it to message you).";
            } elseif (str_contains(strtolower($desc), 'bot was blocked')) {
                $desc .= " — (Action required: The bot is blocked by this user in Telegram).";
            } elseif (str_contains(strtolower($desc), 'unauthorized')) {
                $desc .= " — (Action required: The Bot Token is invalid or was revoked by @BotFather).";
            }

            Log::warning('Telegram send failed', [
                'chat_id'  => $cleanChatId,
                'response' => $json,
            ]);

            return ['success' => false, 'error' => $desc];
        } catch (Throwable $e) {
            Log::error('Telegram HTTP exception: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Connection error: ' . $e->getMessage()];
        }
    }

    /**
     * Send full smart order notification with interactive action buttons to all active recipients.
     * NEVER throws — order must always complete regardless of Telegram status.
     */
    public function sendOrderNotification(Order $order): void
    {
        try {
            $recipients = TelegramRecipient::active()->get();
            if ($recipients->isEmpty()) {
                return;
            }

            $token = $this->botToken();
            if (empty($token)) {
                return;
            }

            $message = $this->formatOrderMessage($order);
            $buttons = $this->buildOrderButtons($order);

            $failed = 0;
            foreach ($recipients as $recipient) {
                $result = $this->sendMessageDetailed((string) $recipient->chat_id, $message, $buttons);
                if (!$result['success']) {
                    $failed++;
                }
            }

            if ($failed > 0) {
                AuditLog::log(
                    'telegram.send_failed',
                    'order',
                    $order->id,
                    "Telegram order notification failed for {$failed} of {$recipients->count()} recipient(s) on order #{$order->order_number}"
                );
            }
        } catch (Throwable $e) {
            // Log but NEVER rethrow — the order must not be blocked
            Log::error('TelegramNotificationService::sendOrderNotification exception: ' . $e->getMessage());
            AuditLog::log('telegram.exception', 'order', $order->id ?? 0, 'Telegram exception: ' . $e->getMessage());
        }
    }

    /**
     * Build interactive Telegram inline keyboard action buttons for the order.
     */
    public function buildOrderButtons(Order $order): array
    {
        $buttons = [];
        $adminOrderUrl = url("/admin/orders/{$order->id}");

        // Button 1: Direct link to view and fulfill in Admin
        $buttons[] = [
            ['text' => '📦 فتح وتجهيز الطلب في الإدارة', 'url' => $adminOrderUrl]
        ];

        // Button 2: 1-click WhatsApp customer chat
        $customerPhone = $order->customer_phone ?: ($order->metadata_json['shipping_address']['phone'] ?? null);
        $customerName = $order->customer_name ?: ($order->metadata_json['shipping_address']['name'] ?? 'عميلنا العزيز');

        if ($customerPhone) {
            $waService = app(WhatsAppNotificationService::class);
            $waUrl = $waService->generateWhatsAppDirectUrl(
                $customerPhone, 
                "مرحباً {$customerName} 👑\nبخصوص طلبك رقم #{$order->order_number} من ATELIER Studio Egypt..."
            );
            $buttons[] = [
                ['text' => '💬 مراسلة العميل واتساب فوراً', 'url' => $waUrl]
            ];
        }

        // Button 3: Pinned Google Maps Location
        $shipping = $order->metadata_json['shipping_address'] ?? [];
        $lat = $shipping['latitude'] ?? null;
        $lng = $shipping['longitude'] ?? null;
        if ($lat && $lng) {
            $buttons[] = [
                ['text' => '📍 موقع التسليم على خرائط جوجل', 'url' => "https://maps.google.com/?q={$lat},{$lng}"]
            ];
        }

        return $buttons;
    }

    /**
     * Send a test message to all active recipients.
     */
    public function sendTestMessage(): array
    {
        $storeName = Setting::get('store_name', 'ATELIER Studio');
        $recipients = TelegramRecipient::active()->get();

        if ($recipients->isEmpty()) {
            return ['success' => false, 'message' => 'لم يتم العثور على مستلمين نشطين. أضف مستلماً واحداً على الأقل أولاً.'];
        }

        $token = $this->botToken();
        if (empty($token)) {
            return ['success' => false, 'message' => 'رمز البوت غير مضبوط. يرجى حفظ Bot Token أولاً.'];
        }

        $botCheck = $this->verifyBot();
        if (!$botCheck['ok']) {
            return [
                'success' => false,
                'message' => "❌ خطأ في رمز بوت تيليجرام: {$botCheck['error']}. تأكد من الرمز المستخرج من @BotFather."
            ];
        }

        $botUser = isset($botCheck['username']) ? "@{$botCheck['username']}" : 'بوت الإشعارات';
        $text = "👑 <b>تجربة إشعار البوت الذكي — {$storeName}</b>\n\n" .
                "✅ يعمل البوت بنجاح عبر {$botUser}!\n" .
                "ستصلك إشعارات فورية بكل طلب جديد مع أزرار تفاعلية لمراسلة العميل وتحديد مكانه على الخريطة.";

        $testButtons = [
            [
                ['text' => '🛍️ تصفح المتجر الحي', 'url' => url('/')],
                ['text' => '⚙️ لوحة التحكم', 'url' => url('/admin')]
            ]
        ];

        $succeeded = 0;
        $errors = [];

        foreach ($recipients as $recipient) {
            $result = $this->sendMessageDetailed((string) $recipient->chat_id, $text, $testButtons);
            if ($result['success']) {
                $succeeded++;
            } else {
                $errors[] = "• <b>{$recipient->label}</b> (Chat ID: {$recipient->chat_id}): {$result['error']}";
            }
        }

        if ($succeeded === $recipients->count()) {
            return [
                'success' => true,
                'message' => "✅ تم إرسال الرسالة الاختبارية بنجاح إلى جميع المستلمين ({$succeeded}) عبر {$botUser}!"
            ];
        }

        if ($succeeded === 0) {
            return [
                'success' => false,
                'message' => "❌ فشل الإرسال لجميع المستلمين:\n" . implode("\n", $errors)
            ];
        }

        return [
            'success' => true,
            'message' => "⚠️ تم الإرسال إلى {$succeeded} من أصل {$recipients->count()} مستلم.\nالأخطاء:\n" . implode("\n", $errors)
        ];
    }

    private function formatOrderMessage(Order $order): string
    {
        $storeName = Setting::get('store_name', 'ATELIER Studio');

        if (!$order->relationLoaded('items')) {
            $order->load(['items.product', 'items.variant']);
        } else {
            $order->loadMissing(['items.product', 'items.variant']);
        }

        $itemLines = '';
        foreach ($order->items as $item) {
            $priceEgp = number_format($item->total_price_minor / 100, 2);
            $skuBadge = $item->sku ? " [<code>{$item->sku}</code>]" : '';
            $supplierUrl = $item->metadata_json['supplier_product_url'] ?? $item->product?->attributes_json['supplier_product_url'] ?? null;
            $supplierLink = filter_var($supplierUrl, FILTER_VALIDATE_URL)
                ? "\n     🔗 <a href=\"" . e($supplierUrl) . "\">فتح رابط المورد</a>"
                : '';
            $variantTitle = $item->metadata_json['variant_title'] ?? $item->variant?->title ?? null;
            $colorHex = $item->metadata_json['color_hex'] ?? $item->variant?->attributes_json['color_hex'] ?? null;
            $choiceLine = $variantTitle ? "\n     🎨 <b>اللون / الاختيار:</b> " . e($variantTitle) . ($colorHex ? " (<code>" . e($colorHex) . "</code>)" : '') : '';
            $itemLines .= "  ▫️ <b>{$item->product_title}</b>{$skuBadge} × {$item->quantity} — <b>{$priceEgp} EGP</b>{$choiceLine}{$supplierLink}\n";
        }
        if (empty($itemLines)) {
            $itemLines = "  ▫️ (قطع حصرية)\n";
        }

        $totalEgp     = number_format($order->total_amount_minor / 100, 2);
        $shippingEgp  = number_format(($order->shipping_minor ?? 0) / 100, 2);
        $subtotalEgp  = number_format(($order->subtotal_minor ?? 0) / 100, 2);
        $meta         = $order->metadata_json ?? [];
        $addr         = $meta['shipping_address'] ?? [];
        $addrLine     = trim(implode(', ', array_filter([
            $addr['street']  ?? '',
            $addr['city']    ?? '',
            $addr['state']   ?? '',
            $addr['country'] ?? 'EG',
        ])));

        $customerName  = $order->customer_name  ?: ($addr['name']  ?? 'عميل زائر');
        $customerPhone = $order->customer_phone ?: ($addr['phone'] ?? 'N/A');
        $paymentMethod = match ($order->payment_method) {
            'cod'     => 'الدفع عند الاستلام (COD) 💵',
            'paymob'  => 'مدفوع إلكترونياً (Paymob) 💳',
            'stripe'  => 'بطاقة بنكية (Stripe) 💳',
            default   => ucfirst($order->payment_method ?? 'COD'),
        };

        // Customer loyalty status (Repeat vs New)
        $orderCount = Order::where('customer_email', $order->customer_email)->count();
        $customerTier = $orderCount > 1 ? "⭐️ عميل متكرر VIP (الطلب رقم {$orderCount})" : "✨ عميل جديد";

        $zone = !empty($meta['shipping_zone']) ? "🌐 <b>منطقة الشحن:</b> {$meta['shipping_zone']}\n" : '';
        
        $coordsInfo = '';
        if (!empty($addr['latitude']) && !empty($addr['longitude'])) {
            $coordsInfo = "📍 <b>إحداثيات GPS:</b> <code>{$addr['latitude']}, {$addr['longitude']}</code>\n";
        }

        return <<<MSG
👑 <b>طلب شراء فاخر جديد — {$storeName}</b>
━━━━━━━━━━━━━━━━━━━
🏷️ <b>رقم الطلب:</b> <code>#{$order->order_number}</code>
👤 <b>العميل:</b> <b>{$customerName}</b> ({$customerTier})
📞 <b>الهاتف:</b> <code>{$customerPhone}</code>
📧 <b>الإيميل:</b> {$order->customer_email}

📦 <b>القطع المطلوبة:</b>
{$itemLines}
💵 <b>قيمة المنتجات:</b> {$subtotalEgp} EGP
🚚 <b>الشحن:</b> {$shippingEgp} EGP
💰 <b>الإجمالي النهائي:</b> <b>{$totalEgp} EGP</b>
💳 <b>طريقة السداد:</b> {$paymentMethod}
━━━━━━━━━━━━━━━━━━━
📍 <b>عنوان التوصيل:</b> {$addrLine}
{$coordsInfo}{$zone}
MSG;
    }
}
