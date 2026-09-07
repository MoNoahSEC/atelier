<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\ShippingZone;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingController extends Controller
{
    public function index(): View
    {
        $zones = ShippingZone::orderBy('sort_order')->get();
        $settings = Setting::allAsMap();

        return view('admin.shipping.index', compact('zones', 'settings'));
    }

    public function storeZone(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:150',
            'governorates'   => 'required|string',
            'rate'           => 'required|numeric|min:0',
            'estimated_days' => 'required|string|max:100',
            'sort_order'     => 'nullable|integer|min:0',
        ]);

        $govs = array_values(array_filter(array_map('trim', explode(',', $request->input('governorates')))));

        $zone = ShippingZone::create([
            'name'              => $request->input('name'),
            'governorates_json' => $govs,
            'rate_minor'        => (int) round(((float) $request->input('rate')) * 100),
            'estimated_days'    => $request->input('estimated_days'),
            'sort_order'        => (int) ($request->input('sort_order') ?? 10),
            'is_active'         => true,
        ]);

        AuditLog::log('shipping.zone_create', 'shipping_zone', $zone->id, "Created shipping zone: {$zone->name}");

        return back()->with('success', "Shipping zone \"{$zone->name}\" created.");
    }

    public function updateZone(Request $request, int $id)
    {
        $zone = ShippingZone::findOrFail($id);

        $request->validate([
            'name'           => 'required|string|max:150',
            'governorates'   => 'required|string',
            'rate'           => 'required|numeric|min:0',
            'estimated_days' => 'required|string|max:100',
            'sort_order'     => 'nullable|integer|min:0',
        ]);

        $govs = array_values(array_filter(array_map('trim', explode(',', $request->input('governorates')))));

        $zone->update([
            'name'              => $request->input('name'),
            'governorates_json' => $govs,
            'rate_minor'        => (int) round(((float) $request->input('rate')) * 100),
            'estimated_days'    => $request->input('estimated_days'),
            'sort_order'        => (int) ($request->input('sort_order') ?? $zone->sort_order),
            'is_active'         => $request->boolean('is_active', true),
        ]);

        AuditLog::log('shipping.zone_update', 'shipping_zone', $zone->id, "Updated shipping zone: {$zone->name}");

        return back()->with('success', "Shipping zone \"{$zone->name}\" updated.");
    }

    public function deleteZone(int $id)
    {
        $zone = ShippingZone::findOrFail($id);
        $name = $zone->name;
        $zone->delete();

        AuditLog::log('shipping.zone_delete', 'shipping_zone', $id, "Deleted shipping zone: {$name}");

        return back()->with('success', "Shipping zone \"{$name}\" deleted.");
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'default_shipping_rate'   => 'required|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'tax_percentage'          => 'nullable|numeric|min:0|max:100',
            'tax_enabled'             => 'nullable|boolean',
        ]);

        Setting::set('default_shipping_rate', (string) $request->input('default_shipping_rate'));
        Setting::set('free_shipping_threshold', (string) ($request->input('free_shipping_threshold') ?? ''));
        Setting::set('tax_percentage', (string) ($request->input('tax_percentage') ?? '0'));
        Setting::set('tax_enabled', $request->boolean('tax_enabled') ? '1' : '0');

        AuditLog::log('settings.shipping', 'setting', 0, 'Updated global shipping rates, free shipping threshold, and tax configuration.');

        return back()->with('success', 'Shipping and tax rules updated successfully.');
    }
}
