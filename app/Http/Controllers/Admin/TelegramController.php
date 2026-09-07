<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\TelegramRecipient;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TelegramController extends Controller
{
    public function __construct(private TelegramNotificationService $telegram) {}

    public function index(): View
    {
        $botToken   = Setting::get('telegram_bot_token', '');
        $recipients = TelegramRecipient::latest()->get();
        $storeName  = Setting::get('store_name', 'ATELIER');

        // Check if token is verified with Telegram
        $botInfo = !empty($botToken) ? $this->telegram->verifyBot() : null;

        return view('admin.telegram.index', compact('botToken', 'recipients', 'storeName', 'botInfo'));
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'telegram_bot_token' => 'required|string|min:20|max:250',
        ]);

        $rawToken = (string) $request->input('telegram_bot_token');
        $clean = trim($rawToken);
        $clean = preg_replace('#^https?://api\.telegram\.org/bot#i', '', $clean);
        $clean = preg_replace('#^bot#i', '', $clean);
        $clean = trim($clean, " \t\n\r\0\x0B'\"");

        Setting::set('telegram_bot_token', $clean);
        AuditLog::log('telegram.token_updated', 'setting', 0, 'Telegram bot token updated by ' . (auth()->user()->name ?? 'Admin'));

        // Verify token immediately
        $check = $this->telegram->verifyBot();
        if ($check['ok']) {
            $botName = $check['username'] ? "@{$check['username']}" : $check['name'];
            return back()->with('success', "✅ Bot Token verified and connected to {$botName}! Now make sure you send /start to your bot in Telegram before testing.");
        }

        return back()->with('error', "⚠️ Token saved, but Telegram returned an error: {$check['error']}. Check the token from @BotFather.");
    }

    public function addRecipient(Request $request)
    {
        $request->validate([
            'label'   => 'required|string|max:100',
            'chat_id' => 'required|string|max:50',
        ]);

        $cleanChatId = trim(filter_var($request->input('chat_id'), FILTER_SANITIZE_NUMBER_INT));
        if (empty($cleanChatId)) {
            $cleanChatId = trim((string)$request->input('chat_id'));
        }

        TelegramRecipient::create([
            'label'     => trim($request->input('label')),
            'chat_id'   => $cleanChatId,
            'is_active' => true,
        ]);

        AuditLog::log('telegram.recipient_added', 'setting', 0, 'Telegram recipient added: ' . $request->input('label'));

        return back()->with('success', 'Recipient "' . $request->input('label') . '" added (Chat ID: ' . $cleanChatId . '). IMPORTANT: Open your bot in Telegram and press START if you haven\'t already.');
    }

    public function toggleRecipient(int $id)
    {
        $recipient            = TelegramRecipient::findOrFail($id);
        $recipient->is_active = !$recipient->is_active;
        $recipient->save();

        $status = $recipient->is_active ? 'activated' : 'paused';
        return back()->with('success', "Recipient \"{$recipient->label}\" {$status}.");
    }

    public function deleteRecipient(int $id)
    {
        $recipient = TelegramRecipient::findOrFail($id);
        $label     = $recipient->label;
        $recipient->delete();

        AuditLog::log('telegram.recipient_deleted', 'setting', 0, "Telegram recipient deleted: {$label}");
        return back()->with('success', "Recipient \"{$label}\" removed.");
    }

    public function sendTest()
    {
        $result = $this->telegram->sendTestMessage();

        $flashKey = $result['success'] ? 'success' : 'error';
        return back()->with($flashKey, $result['message']);
    }
}
