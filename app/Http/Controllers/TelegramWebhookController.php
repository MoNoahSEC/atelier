<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TelegramRecipient;
use App\Services\TelegramBotAssistantService;
use App\Services\TelegramNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function __construct(
        protected TelegramBotAssistantService $assistant,
        protected TelegramNotificationService $telegram
    ) {}

    /**
     * Webhook endpoint called by Telegram servers on new incoming messages/commands.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (!empty($payload)) {
            $this->assistant->handleIncomingUpdate($payload);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Admin action to poll latest updates from Telegram on demand.
     */
    public function sync(): RedirectResponse
    {
        $result = $this->assistant->pollUpdates();

        if ($result['success']) {
            $count = $result['processed_count'] ?? 0;
            return back()->with('success', "تم فحص رسائل وأوامر البوت بنجاح (تمت معالجة {$count} تفاعل).");
        }

        return back()->with('error', "فشل سحب تحديثات البوت: " . ($result['message'] ?? 'خطأ غير معروف'));
    }

    /**
     * Admin action to dispatch the interactive Smart Dashboard with persistent buttons to all registered recipients.
     */
    public function sendDashboard(): RedirectResponse
    {
        $recipients = TelegramRecipient::active()->get();

        if ($recipients->isEmpty()) {
            return back()->with('error', 'لا يوجد مستلمون نشطون. أضف Chat ID أولاً.');
        }

        $sent = 0;
        foreach ($recipients as $recipient) {
            $res = $this->assistant->sendDashboard((string) $recipient->chat_id);
            if ($res['ok'] ?? false) {
                $sent++;
            }
        }

        return back()->with('success', "تم إرسال لوحة التحكم والأزرار التفاعلية الذكية إلى ({$sent}) مستلم على تيليجرام بنجاح! تفقد البوت الآن.");
    }
}
