<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(User::orderBy('name')->get(['id', 'name', 'email', 'role', 'is_active', 'last_login_at']));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,super_admin',
            'location_ids' => 'nullable|array',
        ]);

        $user = User::create($validated);
        return response()->json($user, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $data = $request->only(['name', 'email', 'role', 'location_ids', 'is_active']);
        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }
        $user->update($data);
        return response()->json($user);
    }

    public function destroy(int $id): JsonResponse
    {
        User::findOrFail($id)->update(['is_active' => false]);
        return response()->json(['message' => 'User deactivated']);
    }
}
