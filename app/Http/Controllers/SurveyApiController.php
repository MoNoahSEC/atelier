<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SurveyApiController extends Controller
{
    public function respond(Request $request, int $id): JsonResponse
    {
        $survey = Survey::where('id', $id)->where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'rating'          => 'nullable|integer|min:1|max:5',
            'selected_option' => 'nullable|string|max:255',
            'response_text'   => 'nullable|string|max:2000',
            'customer_name'   => 'nullable|string|max:150',
            'customer_email'  => 'nullable|email|max:150',
            'order_id'        => 'nullable|integer|exists:orders,id',
        ]);

        $user = Auth::user();

        $response = SurveyResponse::create([
            'survey_id'       => $survey->id,
            'user_id'         => $user?->id,
            'order_id'        => $validated['order_id'] ?? null,
            'rating'          => $validated['rating'] ?? null,
            'selected_option' => $validated['selected_option'] ?? null,
            'response_text'   => $validated['response_text'] ?? null,
            'customer_name'   => $validated['customer_name'] ?? ($user?->name),
            'customer_email'  => $validated['customer_email'] ?? ($user?->email),
            'ip_address'      => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'شكراً لمشاركتك رأيك القيّم! نسعى دائماً لتقديم الأفضل.',
            'id'      => $response->id,
        ]);
    }
}
