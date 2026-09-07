<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $query = Review::with('product')->latest();

        if ($request->filled('status')) {
            if ($request->input('status') === 'approved') {
                $query->where('is_approved', true);
            } elseif ($request->input('status') === 'pending') {
                $query->where('is_approved', false);
            }
        }

        if ($request->filled('rating')) {
            $query->where('rating', (int) $request->input('rating'));
        }

        $reviews = $query->paginate(20)->withQueryString();

        $counts = [
            'all'      => Review::count(),
            'approved' => Review::where('is_approved', true)->count(),
            'pending'  => Review::where('is_approved', false)->count(),
        ];

        return view('admin.reviews.index', compact('reviews', 'counts'));
    }

    public function approve(int $id)
    {
        $review = Review::findOrFail($id);
        $review->is_approved = true;
        $review->save();

        AuditLog::log('review.approve', 'review', $review->id, "Approved review for product #{$review->product_id}");

        return back()->with('success', 'Review approved and published to product page.');
    }

    public function reject(int $id)
    {
        $review = Review::findOrFail($id);
        $review->is_approved = false;
        $review->save();

        AuditLog::log('review.reject', 'review', $review->id, "Hidden review #{$review->id}");

        return back()->with('success', 'Review status set to pending / hidden.');
    }

    public function destroy(int $id)
    {
        $review = Review::findOrFail($id);
        $review->delete();

        AuditLog::log('review.delete', 'review', $id, "Deleted review #{$id}");

        return back()->with('success', 'Review deleted.');
    }
}
