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

        // Always create a fresh customer per guest booking so the name/email/phone
        // entered on the form are snapshotted to that booking. Reusing+updating an
        // existing record by email would retroactively change the displayed name on
        // every past booking that points to that customer_id.
        $customer = Customer::create(array_merge($validated, [
            'uuid' => Str::uuid(),
            'whatsapp_opt_in' => $validated['whatsapp_opt_in'] ?? false,
        ]));

        $token = $customer->createToken('guest-booking')->plainTextToken;

        return response()->json([
            'customer_id' => $customer->id,
            'full_name' => $customer->full_name,
            'email' => $customer->email,
            'token' => $token,
        ], 201);
    }
}
