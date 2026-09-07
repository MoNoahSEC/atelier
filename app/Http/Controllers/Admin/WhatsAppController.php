<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppController extends Controller
{
    public function index(WhatsAppNotificationService $service): View
    {
        $instanceId = Setting::get('whatsapp_instance_id', '');
        $apiToken   = Setting::get('whatsapp_api_token', '');
        $enabled    = in_array((string)Setting::get('whatsapp_enabled', '1'), ['1', 'true', 'on', 'yes'], true);
        $provider   = Setting::get('whatsapp_provider', 'ultramsg');

        $connectionStatus = $service->verifyConnection();

        return view('admin.whatsapp.index', compact(
            'instanceId',
            'apiToken',
            'enabled',
            'provider',
            'connectionStatus'
        ));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'whatsapp_instance_id' => 'nullable|string|max:100',
            'whatsapp_api_token'   => 'nullable|string|max:200',
            'whatsapp_provider'    => 'required|in:ultramsg,generic',
        ]);

        Setting::set('whatsapp_instance_id', trim((string)$request->input('whatsapp_instance_id', '')));
        Setting::set('whatsapp_api_token', trim((string)$request->input('whatsapp_api_token', '')));
        Setting::set('whatsapp_provider', $request->input('whatsapp_provider'));
        Setting::set('whatsapp_enabled', $request->has('whatsapp_enabled') ? '1' : '0');

        AuditLog::log('whatsapp.settings_updated', 'setting', 0, 'Updated WhatsApp gateway credentials and status.');

        return back()->with('success', 'WhatsApp settings and gateway configuration saved successfully!');
    }

    public function sendTestMessage(Request $request, WhatsAppNotificationService $service): RedirectResponse
    {
        $request->validate([
            'test_phone'   => 'required|string|max:30',
            'test_message' => 'nullable|string|max:500',
        ]);

        $phone = $request->input('test_phone');
        $msg = $request->input('test_message') ?: "🧪 تجربة إشعار واتساب ناجحة من متجر ATELIER Studio Egypt!\nتاريخ الإرسال: " . now()->format('Y-m-d H:i:s');

        $res = $service->sendCustomMessage($phone, $msg);

        if ($res['success']) {
            return back()->with('success', "Test WhatsApp message sent successfully to {$phone}! (Msg ID: " . ($res['message_id'] ?? 'N/A') . ")");
        }

        $directUrl = $res['direct_url'] ?? $service->generateWhatsAppDirectUrl($phone, $msg);
        return back()->with('error', "Could not dispatch via API: {$res['error']}. Click here to send directly: {$directUrl}");
    }
}
