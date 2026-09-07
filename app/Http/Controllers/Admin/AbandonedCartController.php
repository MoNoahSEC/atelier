<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbandonedCartController extends Controller
{
    public function index(Request $request): View
    {
        $query = AbandonedCart::with(['user', 'product'])
            ->latest('abandoned_at');

        if ($request->filled('status')) {
            if ($request->input('status') === 'followed_up') {
                $query->whereNotNull('followed_up_at');
            } else {
                $query->whereNull('followed_up_at');
            }
        }

        $carts = $query->paginate(25)->withQueryString();

        $totalAbandoned  = AbandonedCart::count();
        $pendingFollowUp = AbandonedCart::whereNull('followed_up_at')->count();

        return view('admin.abandoned-carts.index', compact('carts', 'totalAbandoned', 'pendingFollowUp'));
    }

    public function markFollowedUp(Request $request, int $id)
    {
        $cart = AbandonedCart::findOrFail($id);
        $cart->followed_up_at = now();
        $cart->follow_up_note = $request->input('note');
        $cart->save();

        return back()->with('success', 'Cart marked as followed up.');
    }
}
