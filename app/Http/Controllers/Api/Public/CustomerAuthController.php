<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    /**
     * Register a new customer account.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|max:255|unique:customers,email',
            'password'   => 'required|string|min:8|confirmed',
        ]);

        $customer = Customer::create([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => mb_strtolower(trim($validated['email'])),
            'password'   => Hash::make($validated['password']),
        ]);

        $token = $customer->createToken('customer-token', ['customer'])->plainTextToken;

        return response()->json([
            'customer' => [
                'id'         => $customer->id,
                'first_name' => $customer->first_name,
                'last_name'  => $customer->last_name,
                'email'      => $customer->email,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * Log in an existing customer.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $customer = Customer::where('email', mb_strtolower(trim($validated['email'])))->first();

        if (!$customer || !$customer->password || !Hash::check($validated['password'], $customer->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        // Revoke previous tokens and issue a fresh one
        $customer->tokens()->where('name', 'customer-token')->delete();
        $token = $customer->createToken('customer-token', ['customer'])->plainTextToken;

        return response()->json([
            'customer' => [
                'id'         => $customer->id,
                'first_name' => $customer->first_name,
                'last_name'  => $customer->last_name,
                'email'      => $customer->email,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Log out the authenticated customer.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Return the authenticated customer's profile.
     */
    public function me(Request $request): JsonResponse
    {
        $customer = $request->user();

        return response()->json([
            'id'         => $customer->id,
            'first_name' => $customer->first_name,
            'last_name'  => $customer->last_name,
            'email'      => $customer->email,
            'phone'      => $customer->phone,
            'status'     => $customer->status,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $customer = $request->user();
        
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name'  => 'sometimes|string|max:100',
            'phone'      => 'nullable|string|max:20',
        ]);
        
        $customer->update($validated);
        
        return response()->json([
            'success' => true,
            'customer' => [
                'id'         => $customer->id,
                'first_name' => $customer->first_name,
                'last_name'  => $customer->last_name,
                'email'      => $customer->email,
                'phone'      => $customer->phone,
            ]
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $customer = $request->user();
        
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);
        
        if (!Hash::check($validated['current_password'], $customer->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }
        
        $customer->update([
            'password' => Hash::make($validated['new_password']),
        ]);
        
        return response()->json(['success' => true, 'message' => 'Password updated successfully.']);
    }
}
