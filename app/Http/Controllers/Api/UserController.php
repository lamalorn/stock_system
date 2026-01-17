<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        return User::query()
            ->with('roles:id,name')
            ->select('id','name','email','phone','is_active','last_login_at','created_at')
            ->latest()
            ->paginate(15);
    }

    public function store(UserStoreRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $request->user()?->id,
        ]);

        if (!empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        return response()->json([
            'user' => $user->only(['id','name','email','phone','is_active']),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function update(UserUpdateRequest $request, User $user)
    {
        $data = $request->validated();

        if (isset($data['password']) && $data['password']) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        if (array_key_exists('roles', $data)) {
            $user->syncRoles($data['roles'] ?? []);
        }

        return response()->json(['message' => 'Updated', 'user' => $user]);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
