<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::orderBy('name')->get([
            'id', 'name', 'username', 'email', 'role', 'is_active', 'location_ids', 'last_login_at', 'created_at',
        ]);
        return response()->json($users);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $locations = Location::orderBy('name')->get(['id', 'name']);
        return response()->json([
            'user' => $user->only(['id', 'name', 'username', 'email', 'role', 'is_active', 'location_ids', 'last_login_at']),
            'locations' => $locations,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['admin', 'super_admin'])],
            'location_ids' => 'nullable|array',
            'location_ids.*' => 'integer|exists:locations,id',
            'is_active' => 'boolean',
        ]);

        $user = User::create($validated);
        return response()->json($user, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'username' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['sometimes', 'nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['sometimes', Rule::in(['admin', 'super_admin'])],
            'location_ids' => 'sometimes|nullable|array',
            'location_ids.*' => 'integer|exists:locations,id',
            'is_active' => 'sometimes|boolean',
        ]);

        // Prevent demoting yourself out of super_admin (lockout protection).
        if ($request->user()->id === $user->id && isset($validated['role']) && $validated['role'] !== 'super_admin') {
            return response()->json(['message' => 'You cannot change your own role.'], 422);
        }

        // Prevent deactivating yourself.
        if ($request->user()->id === $user->id && array_key_exists('is_active', $validated) && !$validated['is_active']) {
            return response()->json(['message' => 'You cannot deactivate your own account.'], 422);
        }

        $user->update($validated);
        return response()->json($user);
    }

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $validated = $request->validate(['password' => 'required|string|min:8']);
        $user->update(['password' => $validated['password']]);
        return response()->json(['message' => 'Password updated']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($request->user()->id === $id) {
            return response()->json(['message' => 'You cannot deactivate your own account.'], 422);
        }
        User::findOrFail($id)->update(['is_active' => false]);
        return response()->json(['message' => 'User deactivated']);
    }
}
