<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Invalid credentials']);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages(['account' => 'Account is inactive']);
        }

        $token = $user->createToken('api')->plainTextToken;

        $user->update(['last_login_at' => now()]);

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id','name','email','phone']),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => $user->only(['id','name','email','phone','is_active']),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
