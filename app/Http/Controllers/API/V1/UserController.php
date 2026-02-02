<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search  = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = User::with('role')
            ->whereHas('role', function ($q) {
                $q->whereNotIn('name', ['superadmin', 'participant']);
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data'    => $query->paginate($perPage),
        ]);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => ['required', 'string'],
            'email'             => ['required', 'email', 'unique:users,email'],
            'username'          => ['nullable', 'string'],
            'password'          => ['required', 'string', 'min:6'],
            'role_id'           => ['required', 'exists:roles,id'],
            'can_multiple_role' => ['boolean'],
        ]);

        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
        ]);

        return response()->json([
            'message' => 'User berhasil ditambahkan.',
            'data' => $user,
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'              => ['required', 'string'],
            'email'             => ['required', 'email', 'unique:users,email,' . $user->id],
            'username'          => ['nullable', 'string'],
            'password'          => ['nullable', 'string', 'min:6'],
            'role_id'           => ['required', 'exists:roles,id'],
            'can_multiple_role' => ['boolean'],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User berhasil diperbarui.',
            'data' => $user,
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'User berhasil dihapus.',
        ]);
    }
}
