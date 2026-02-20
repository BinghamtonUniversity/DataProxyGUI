<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * CRUD for GUI (internal) users. Only super admins can access.
 */
class InternalUsersController extends Controller
{
    /**
     * List all internal users.
     */
    public function index(): JsonResponse
    {
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'unique_id', 'super_admin', 'created_at', 'updated_at']);

        return response()->json($users);
    }

    /**
     * Create a new internal user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'unique_id' => ['nullable', 'string', 'max:255', 'unique:users,unique_id'],
            'super_admin' => ['boolean'],
        ]);

        $validated['super_admin'] = $request->boolean('super_admin', false);

        $user = User::create($validated);

        return response()->json($user->only(['id', 'name', 'email', 'unique_id', 'super_admin', 'created_at', 'updated_at']), 201);
    }

    /**
     * Update an internal user.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'unique_id' => ['nullable', 'string', 'max:255', Rule::unique('users', 'unique_id')->ignore($user->id)],
            'super_admin' => ['boolean'],
        ]);

        $user->fill($validated);
        $user->super_admin = $request->boolean('super_admin', false);
        $user->save();

        return response()->json($user->only(['id', 'name', 'email', 'unique_id', 'super_admin', 'created_at', 'updated_at']));
    }

    /**
     * Delete an internal user. Prevents deleting the current user.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }
}
