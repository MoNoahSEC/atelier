<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InlineEditController extends Controller
{
    /**
     * POST /admin/products/{id}/inline-edit
     * Accepts: field (retail_price_minor|inventory), value
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'field' => 'required|in:retail_price_minor,inventory,status',
            'value' => 'required',
        ]);

        $product = Product::findOrFail($id);
        $field   = $request->input('field');
        $raw     = $request->input('value');

        switch ($field) {
            case 'retail_price_minor':
                // Input is in EGP (e.g. 450), stored as minor (45000)
                $value = (int) round((float) $raw * 100);
                if ($value <= 0) {
                    return response()->json(['success' => false, 'message' => 'Price must be positive.'], 422);
                }
                $product->retail_price_minor = $value;
                $formatted = number_format($value / 100, 0) . ' EGP';
                break;

            case 'inventory':
                $value = (int) $raw;
                if ($value < 0) {
                    return response()->json(['success' => false, 'message' => 'Inventory cannot be negative.'], 422);
                }
                $product->inventory = $value;
                $formatted = (string) $value;
                break;

            case 'status':
                $value = in_array($raw, ['active', 'draft', 'archived']) ? $raw : 'draft';
                $product->status = $value;
                $formatted = ucfirst($value);
                break;

            default:
                return response()->json(['success' => false, 'message' => 'Invalid field.'], 422);
        }

        $product->save();

        return response()->json([
            'success'         => true,
            'new_value'       => $value,
            'new_value_formatted' => $formatted,
        ]);
    }
}
