<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles:id,name')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('exclude_role'), function ($query) use ($request) {
                $roleId = Role::where('name', strtoupper($request->exclude_role))->value('id');
                if ($roleId) {
                    $query->where('role_id', '!=', $roleId);
                }
            })
            ->orderBy('role_id')
            ->orderBy('name')
            ->paginate(setting('pagination_limit'));

        return response()->json($users);
    }

    // =========================
    // STORE
    // =========================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'username' => 'nullable|string|max:50|unique:users,username',
            'password' => 'required|string|min:6',
            'role_id'  => 'required|exists:roles,id',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'username' => $validated['username'] ?? null,
                'password' => Hash::make($validated['password']),
                'role_id'  => $validated['role_id'],
                'can_multiple_role' => false,
            ]);

            // Sinkronkan pivot role (role utama saja)
            $user->roles()->sync([$validated['role_id']]);
        });

        return response()->json(['message' => 'User created'], 201);
    }

    // =========================
    // UPDATE
    // =========================
    public function update(Request $request, $id)
    {
        $user = User::with('roles')->findOrFail($id);

        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'username' => 'nullable|string|max:50|unique:users,username,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role_id'  => 'required|exists:roles,id',
        ]);

        DB::transaction(function () use ($user, $validated) {
            $user->update([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'username' => $validated['username'] ?? null,
                'role_id'  => $validated['role_id'],
            ]);

            if (!empty($validated['password'])) {
                $user->update([
                    'password' => Hash::make($validated['password']),
                ]);
            }

            // Jika bukan multi-role, sinkronkan satu role saja
            if (!$user->can_multiple_role) {
                $user->roles()->sync([$validated['role_id']]);
            }
        });

        return response()->json(['message' => 'User updated']);
    }

    // =========================
    // DELETE
    // =========================
    public function destroy($id)
    {
        User::findOrFail($id)->delete();
        return response()->json(['message' => 'User deleted']);
    }

    public function bulkDelete()
    {
        User::whereIn('id', request('ids'))->delete();
        return response()->json(['message' => 'Users deleted successfully']);
    }

    public function fetch()
    {
        return auth()->id();
    }

    // =========================
    // CHANGE ROLE
    // =========================
    public function changeRole(Request $request, User $user)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $targetRole = Role::findOrFail($request->role_id);

        DB::transaction(function () use ($user, $targetRole) {
            $user->update([
                'role_id' => $targetRole->id,
            ]);

            if (!$user->can_multiple_role) {
                $user->roles()->sync([$targetRole->id]);
            } else {
                $user->roles()->syncWithoutDetaching([$targetRole->id]);
            }
        });

        $user->load('roles:id,name');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'role_id' => $user->role_id,
                'roles' => $user->roles->pluck('name'),
                'can_multiple_role' => $user->can_multiple_role,
            ],
        ]);
    }
}
