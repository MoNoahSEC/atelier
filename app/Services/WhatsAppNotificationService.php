<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppNotificationService
{
    /**
     * Get the configured WhatsApp provider (e.g. ultramsg, generic).
     */
    public function provider(): string
    {
        return (string) Setting::get('whatsapp_provider', 'ultramsg');
    }

    /**
     * Check if automated WhatsApp notifications are enabled.
     */
    public function isEnabled(): bool
    {
        $val = Setting::get('whatsapp_enabled', '1');
        return in_array((string)$val, ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * Retrieve Instance ID (e.g. UltraMsg instanceXXXXX).
     */
    public function instanceId(): ?string
    {
        $val = trim((string) Setting::get('whatsapp_instance_id', ''));
        return !empty($val) ? $val : null;
    }

    /**
     * Retrieve API Token / Secret.
     */
    public function apiToken(): ?string
    {
        $val = trim((string) Setting::get('whatsapp_api_token', ''));
        return !empty($val) ? $val : null;
    }

    /**
     * Clean and format phone number for international WhatsApp delivery.
     * UltraMsg requires strictly digits with country code, without '+' or leading zeros:
     * e.g. 01012345678 -> 201012345678
     *      +201012345678 -> 201012345678
     *      00201012345678 -> 201012345678
     */
    public function formatPhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        // Strip double zeros prefix if entered as 0020...
        if (str_starts_with($digits, '0020')) {
            $digits = substr($digits, 2);
        }

        // Egyptian local 11-digit numbers starting with 01 (e.g. 010, 011, 012, 015)
        if (str_starts_with($digits, '01') && strlen($digits) === 11) {
            return '20' . substr($digits, 1);
        }

        // Already has 20 prefix with 12 digits (e.g. 201012345678)
        if (str_starts_with($digits, '20') && strlen($digits) === 12) {
            return $digits;
        }

        return $digits;
    }

    /**
     * Resiliently resolve customer phone number from multiple order sources.
     */
    public function resolveCustomerPhone(Order $order): ?string
    {
        if (!empty($order->customer_phone)) {
            return $order->customer_phone;
        }

        $metaPhone = $order->metadata_json['shipping_address']['phone'] ?? null;
        if (!empty($metaPhone)) {
            return $metaPhone;
        }

        if ($order->customer && !empty($order->customer->phone)) {
            return $order->customer->phone;
        }

        return null;
    }

    /**
     * Resiliently resolve customer name from multiple order sources.
     */
    public function resolveCustomerName(Order $order): string
    {
        if (!empty($order->customer_name)) {
            return $order->customer_name;
        }

        $metaName = $order->metadata_json['shipping_address']['name'] ?? null;
        if (!empty($metaName)) {
            return $metaName;
        }

        if ($order->customer) {
            $fullName = trim(($order->customer->first_name ?? '') . ' ' . ($order->customer->last_name ?? ''));
            if (!empty($fullName)) {
                return $fullName;
            }
        }

        return 'عميلنا المميز';
    }

    /**
     * Verify WhatsApp Gateway connectivity.
     */
    public function verifyConnection(): array
    {
        $instance = $this->instanceId();
        $token = $this->apiToken();

        if (empty($instance) || empty($token)) {
            return [
                'ok'      => false,
                'status'  => 'NOT_CONFIGURED',
                'message' => 'لم يتم إدخال Instance ID أو API Token في الإعدادات بعد.',
            ];
        }

        if ($this->provider() === 'ultramsg') {
            try {
                $response = Http::withoutVerifying()->timeout(8)->get("https://api.ultramsg.com/{$instance}/instance/status", [
                    'token' => $token,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $accountStatus = strtolower($data['status'] ?? 'unknown');
                    $isAuth = ($accountStatus === 'authenticated');
                    
                    Setting::set('whatsapp_last_status', $isAuth ? 'CONNECTED' : strtoupper($accountStatus));

                    return [
                        'ok'      => $isAuth,
                        'status'  => strtoupper($accountStatus),
                        'message' => $isAuth ? 'متصل بنجاح وجاهز لإرسال الرسائل الفورية.' : "حالة الحساب في UltraMsg: {$accountStatus}. تأكد من مسح رمز الـ QR بهاتفك.",
                        'data'    => $data,
                    ];
                }

                $err = $response->json()['message'] ?? 'فشل الاتصال بخادم UltraMsg.';
                Setting::set('whatsapp_last_status', 'ERROR');
                return [
                    'ok'      => false,
                    'status'  => 'ERROR',
                    'message' => 'HTTP ' . $response->status() . ': ' . $err,
                ];
            } catch (Throwable $e) {
                Setting::set('whatsapp_last_status', 'NETWORK_ERROR');
                return [
                    'ok'      => false,
                    'status'  => 'NETWORK_ERROR',
                    'message' => 'خطأ في الشبكة: ' . $e->getMessage(),
                ];
            }
        }

        return [
            'ok'      => true,
            'status'  => 'READY',
            'message' => 'البوابة المباشرة مهيأة.',
        ];
    }

    /**
     * Send order confirmation to customer via WhatsApp with optional native GPS pin.
     */
    public function sendOrderConfirmation(Order $order): array
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'إشعارات واتساب معطلة من الإعدادات.'];
        }

        $phone = $this->resolveCustomerPhone($order);
        if (empty($phone)) {
            return ['success' => false, 'error' => 'الطلب لا يحتوي على رقم هاتف للعميل.'];
        }

        $message = $this->composeOrderConfirmationMessage($order);
        $result = $this->sendCustomMessage($phone, $message);

        // If GPS coordinates exist, also dispatch native WhatsApp Location Pin
        $shipping = $order->metadata_json['shipping_address'] ?? [];
        $lat = $shipping['latitude'] ?? null;
        $lng = $shipping['longitude'] ?? null;
        if ($lat && $lng && $result['success']) {
            $addressName = ($shipping['street'] ?? '') . ' - ' . ($shipping['city'] ?? '');
            $this->sendLocationPin($phone, (float)$lat, (float)$lng, $addressName, "موقع تسليم طلب #{$order->order_number}");
        }

        return $result;
    }

    /**
     * Send status update to customer via WhatsApp.
     */
    public function sendStatusUpdate(Order $order, string $newStatus): array
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'إشعارات واتساب معطلة من الإعدادات.'];
        }

        $phone = $this->resolveCustomerPhone($order);
        if (empty($phone)) {
            return ['success' => false, 'error' => 'الطلب لا يحتوي على رقم هاتف للعميل.'];
        }

        $customerName = $this->resolveCustomerName($order);

        $statusLabels = [
            'processing' => 'قيد التجهيز والتغليف الفاخر 📦',
            'shipped'    => 'خرجت مع مندوب التوصيل السريع 🚚',
            'delivered'  => 'تم التوصيل بنجاح، نتمنى لك تجربة استثنائية! 👑',
            'cancelled'  => 'تم إلغاء الطلب ⚠️',
        ];
        $label = $statusLabels[$newStatus] ?? $newStatus;

        $trackingUrl = url('/track-order?order=' . urlencode($order->order_number));

        $body = "مرحباً {$customerName} 👑\n\n" .
            "تحديث بخصوص طلبك رقم *#{$order->order_number}* من *ATELIER*:\n" .
            "الحالة الحالية: *{$label}*\n\n" .
            "🔗 تتبع مسار الشحنة:\n{$trackingUrl}\n\n" .
            "شكراً لاختيارك ATELIER Studio Egypt.";

        return $this->sendCustomMessage($phone, $body);
    }

    /**
     * Send native WhatsApp GPS Location Card via UltraMsg.
     */
    public function sendLocationPin(string $phone, float $lat, float $lng, string $address, string $name = 'موقع التسليم'): array
    {
        $formattedPhone = $this->formatPhone($phone);
        $instance = $this->instanceId();
        $token = $this->apiToken();

        if (empty($instance) || empty($token)) {
            return ['success' => false, 'error' => 'UltraMsg not configured.'];
        }

        try {
            $response = Http::withoutVerifying()->timeout(10)->asForm()->post("https://api.ultramsg.com/{$instance}/messages/location", [
                'token'   => $token,
                'to'      => $formattedPhone,
                'address' => $address,
                'lat'     => (string)$lat,
                'lng'     => (string)$lng,
                'name'    => $name,
            ]);

            return ['success' => $response->successful()];
        } catch (Throwable $e) {
            Log::warning("WhatsApp location pin send failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send custom WhatsApp text message with diagnostics and fallback.
     */
    public function sendCustomMessage(string $phone, string $message): array
    {
        $formattedPhone = $this->formatPhone($phone);
        $instance = $this->instanceId();
        $token = $this->apiToken();

        $directUrl = $this->generateWhatsAppDirectUrl($phone, $message);

        if (empty($instance) || empty($token)) {
            Log::info("WhatsApp message skipped (no token/instance configured). Target: {$formattedPhone}");
            Setting::set('whatsapp_last_error', 'لم يتم ضبط بيانات الربط (Instance ID / Token) في الإدارة.');
            return [
                'success'    => false,
                'error'      => 'لم يتم ضبط بيانات الربط (Instance ID / Token) في لوحة تحكم الأدمن بعد.',
                'direct_url' => $directUrl,
            ];
        }

        try {
            $response = Http::withoutVerifying()->timeout(12)->asForm()->post("https://api.ultramsg.com/{$instance}/messages/chat", [
                'token' => $token,
                'to'    => $formattedPhone,
                'body'  => $message,
            ]);

            $json = $response->json();

            if ($response->successful() && isset($json['sent']) && ($json['sent'] === 'true' || $json['sent'] === true)) {
                AuditLog::log('whatsapp.sent', 'order', 0, "WhatsApp notification sent to {$formattedPhone}. Msg ID: " . ($json['id'] ?? 'n/a'));
                Setting::set('whatsapp_last_status', 'DELIVERED');
                Setting::set('whatsapp_last_error', '');
                return [
                    'success'    => true,
                    'message_id' => $json['id'] ?? null,
                    'direct_url' => $directUrl,
                ];
            }

            $errMsg = $json['message'] ?? ($json['error'] ?? ('HTTP ' . $response->status() . ': ' . $response->body()));
            Log::warning("WhatsApp send failed for {$formattedPhone}: {$errMsg}");
            Setting::set('whatsapp_last_error', $errMsg);

            return [
                'success'    => false,
                'error'      => $errMsg,
                'direct_url' => $directUrl,
            ];
        } catch (Throwable $e) {
            Log::error("WhatsApp API exception for {$formattedPhone}: " . $e->getMessage());
            Setting::set('whatsapp_last_error', $e->getMessage());
            return [
                'success'    => false,
                'error'      => $e->getMessage(),
                'direct_url' => $directUrl,
            ];
        }
    }

    /**
     * Generate 1-click Click-to-Chat direct WhatsApp link for admin / customer fallback.
     */
    public function generateWhatsAppDirectUrl(string $phone, string $text = ''): string
    {
        $cleaned = $this->formatPhone($phone);
        $encoded = rawurlencode($text);
        return "https://wa.me/{$cleaned}?text={$encoded}";
    }

    /**
     * Compose comprehensive luxury interactive order confirmation receipt.
     */
    public function composeOrderConfirmationMessage(Order $order): string
    {
        $customerName = $this->resolveCustomerName($order);

        $itemsText = '';
        foreach ($order->items as $item) {
            $price = number_format($item->unit_price_minor / 100, 2);
            $variantTitle = $item->metadata_json['variant_title'] ?? $item->variant?->title ?? null;
            $colorHex = $item->metadata_json['color_hex'] ?? $item->variant?->attributes_json['color_hex'] ?? null;
            $choiceLine = $variantTitle ? "\n   🎨 اللون / الاختيار: *{$variantTitle}*" . ($colorHex ? " ({$colorHex})" : '') : '';
            $itemsText .= "▪️ *{$item->product_title}* × {$item->quantity}{$choiceLine}\n   السعر: {$price} {$order->currency}\n";
        }
        if (empty($itemsText)) {
            $itemsText = "▪️ *قطع ATELIER الحصرية المختارة*\n";
        }

        $totalFormatted = number_format($order->total_amount_minor / 100, 2);
        $paymentLabel = match ($order->payment_method) {
            'cod'    => 'الدفع نقدياً عند الاستلام (COD) 💵',
            'paymob' => 'مدفوع إلكترونياً (Paymob Gateway) 💳',
            'stripe' => 'مدفوع إلكترونياً بالبطاقة (Stripe) 💳',
            default  => strtoupper($order->payment_method ?? 'COD'),
        };

        $shippingData = $order->metadata_json['shipping_address'] ?? [];
        $addressParts = array_filter([
            $shippingData['street'] ?? '',
            $shippingData['city'] ?? '',
            $shippingData['state'] ?? '',
        ]);
        $addressLine = !empty($addressParts) ? implode(', ', $addressParts) : ($order->notes ?? 'العنوان المحدد في الطلب');

        // Pinned GPS link
        $mapsSection = '';
        $lat = $shippingData['latitude'] ?? null;
        $lng = $shippingData['longitude'] ?? null;
        if ($lat && $lng) {
            $mapsSection = "📍 *موقع التسليم المثبت على الخريطة:*\nhttps://maps.google.com/?q={$lat},{$lng}\n";
        }

        $trackingUrl = url('/track-order?order=' . urlencode($order->order_number));

        return "👑 *ATELIER Studio Egypt* 👑\n" .
            "━━━━━━━━━━━━━━━━━━━\n" .
            "مرحباً بك عزيزنا *{$customerName}* ✨\n\n" .
            "تم استلام وتأكيد طلبك الفاخر بنجاح:\n" .
            "رقم الطلب: *#{$order->order_number}*\n" .
            "تاريخ التسجيل: *" . now()->format('Y-m-d H:i') . "*\n\n" .
            "📦 *محتويات الطلب:*\n{$itemsText}\n" .
            "💵 *الإجمالي النهائي:* *{$totalFormatted} {$order->currency}*\n" .
            "💳 *طريقة السداد:* {$paymentLabel}\n" .
            "📍 *عنوان الشحن:* {$addressLine}\n" .
            ($mapsSection ? "{$mapsSection}\n" : "\n") .
            "🚚 *تتبع خط سير شحنتك لحظياً:*\n{$trackingUrl}\n\n" .
            "━━━━━━━━━━━━━━━━━━━\n" .
            "✨ جميع منتجاتنا مغلفة يدوياً بعناية في بوكس ATELIER الفاخر.\n" .
            "📞 مندوب الشحن الخاص سيتواصل معك قبل التوصيل للتنسيق.\n" .
            "💬 يسعدنا خدمتك والرد على أي استفسار في هذه المحادثة مباشرة 🤝";
    }
}
