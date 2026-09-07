<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        $coupons = Coupon::latest()->paginate(20);
        return view('admin.coupons.index', compact('coupons'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'                   => 'required|string|max:50|unique:coupons,code',
            'type'                   => 'required|in:percentage,fixed',
            'value'                  => 'required|numeric|min:1',
            'min_order_amount'       => 'nullable|numeric|min:0',
            'max_discount'           => 'nullable|numeric|min:0',
            'max_uses'               => 'nullable|integer|min:1',
            'expires_at'             => 'nullable|date|after:today',
            'is_active'              => 'nullable|boolean',
        ]);

        $val = (int) $request->input('value');
        if ($request->input('type') === 'fixed') {
            $val = (int) round(((float)$request->input('value')) * 100);
        }

        $coupon = Coupon::create([
            'code'                   => strtoupper(trim($request->input('code'))),
            'type'                   => $request->input('type'),
            'value'                  => $val,
            'min_order_amount_minor' => $request->filled('min_order_amount') ? (int) round(((float)$request->min_order_amount) * 100) : 0,
            'max_discount_minor'     => $request->filled('max_discount') ? (int) round(((float)$request->max_discount) * 100) : null,
            'max_uses'               => $request->filled('max_uses') ? (int) $request->max_uses : null,
            'used_count'             => 0,
            'is_active'              => $request->boolean('is_active', true),
            'expires_at'             => $request->filled('expires_at') ? $request->input('expires_at') : null,
        ]);

        AuditLog::log('coupon.create', 'coupon', $coupon->id, "Created promotional coupon: {$coupon->code}");

        return back()->with('success', "Coupon \"{$coupon->code}\" created successfully!");
    }

    public function toggle(int $id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->is_active = !$coupon->is_active;
        $coupon->save();

        AuditLog::log('coupon.toggle', 'coupon', $coupon->id, "Toggled coupon {$coupon->code} to " . ($coupon->is_active ? 'active' : 'inactive'));

        return back()->with('success', "Coupon {$coupon->code} is now " . ($coupon->is_active ? 'ACTIVE' : 'INACTIVE'));
    }

    public function destroy(int $id)
    {
        $coupon = Coupon::findOrFail($id);
        $code = $coupon->code;
        $coupon->delete();

        AuditLog::log('coupon.delete', 'coupon', $id, "Deleted coupon {$code}");

        return back()->with('success', "Coupon \"{$code}\" deleted.");
    }
}
