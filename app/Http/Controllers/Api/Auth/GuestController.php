<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GuestController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|min:8|max:50',
            'country_code' => 'required|string|max:5',
            'whatsapp_opt_in' => 'boolean',
        ]);

        $customer = Customer::firstOrCreate(
            ['email' => $validated['email']],
            array_merge($validated, ['uuid' => Str::uuid()])
        );

        // Update name/phone if existing customer
        if (!$customer->wasRecentlyCreated) {
            $customer->update([
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'],
                'country_code' => $validated['country_code'],
                'whatsapp_opt_in' => $validated['whatsapp_opt_in'] ?? false,
            ]);
        }

        $token = $customer->createToken('guest-booking')->plainTextToken;

        return response()->json([
            'customer_id' => $customer->id,
            'full_name' => $customer->full_name,
            'email' => $customer->email,
            'token' => $token,
        ], 201);
    }
}
