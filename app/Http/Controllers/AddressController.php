<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AddressController extends Controller
{
    /**
     * Store a new address in the user's address book.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'label'          => 'required|string|max:50',
            'full_name'      => 'required|string|max:150',
            'phone'          => 'required|string|max:30',
            'street_address' => 'required|string|max:255',
            'city'           => 'required|string|max:100',
            'state'          => 'nullable|string|max:100',
            'postal_code'    => 'nullable|string|max:30',
            'country_code'   => 'nullable|string|size:2',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'is_default'     => 'nullable|boolean',
        ]);

        $isDefault = !empty($validated['is_default']) || $user->addresses()->count() === 0;

        if ($isDefault) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create([
            'label'          => $validated['label'],
            'full_name'      => $validated['full_name'],
            'phone'          => $validated['phone'],
            'street_address' => $validated['street_address'],
            'city'           => $validated['city'],
            'state'          => $validated['state'] ?? null,
            'postal_code'    => $validated['postal_code'] ?? null,
            'country_code'   => $validated['country_code'] ?? 'EG',
            'latitude'       => $validated['latitude'] ?? null,
            'longitude'      => $validated['longitude'] ?? null,
            'is_default'     => $isDefault,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Address saved successfully.',
                'address' => $address,
            ], 201);
        }

        return back()->with('success', 'Address added to your address book.');
    }

    /**
     * Update an existing address.
     */
    public function update(Request $request, Address $address): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $address);

        $validated = $request->validate([
            'label'          => 'required|string|max:50',
            'full_name'      => 'required|string|max:150',
            'phone'          => 'required|string|max:30',
            'street_address' => 'required|string|max:255',
            'city'           => 'required|string|max:100',
            'state'          => 'nullable|string|max:100',
            'postal_code'    => 'nullable|string|max:30',
            'country_code'   => 'nullable|string|size:2',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'is_default'     => 'nullable|boolean',
        ]);

        $isDefault = !empty($validated['is_default']);

        if ($isDefault) {
            $address->makeDefault();
        }

        $address->update([
            'label'          => $validated['label'],
            'full_name'      => $validated['full_name'],
            'phone'          => $validated['phone'],
            'street_address' => $validated['street_address'],
            'city'           => $validated['city'],
            'state'          => $validated['state'] ?? null,
            'postal_code'    => $validated['postal_code'] ?? null,
            'country_code'   => $validated['country_code'] ?? 'EG',
            'latitude'       => $validated['latitude'] ?? $address->latitude,
            'longitude'      => $validated['longitude'] ?? $address->longitude,
            'is_default'     => $isDefault || $address->is_default,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Address updated successfully.',
                'address' => $address,
            ]);
        }

        return back()->with('success', 'Address updated successfully.');
    }

    /**
     * Delete an address from the address book.
     */
    public function destroy(Request $request, Address $address): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $address);

        $wasDefault = $address->is_default;
        $userId = $address->user_id;

        $address->delete();

        // If the deleted address was default, promote the latest remaining address to default
        if ($wasDefault) {
            $next = Address::where('user_id', $userId)->latest()->first();
            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Address deleted.',
            ]);
        }

        return back()->with('success', 'Address deleted successfully.');
    }

    /**
     * Mark an address as the default delivery address.
     */
    public function setDefault(Request $request, Address $address): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $address);

        $address->makeDefault();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Default delivery address updated.',
            ]);
        }

        return back()->with('success', 'Default address set.');
    }
}
