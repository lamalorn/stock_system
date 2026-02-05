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
    // GET /users/summary
    public function summary()
    {
        $total = User::query()->count();
        $disabled = User::query()->where('is_active', false)->count();

        $admins = User::query()
            ->whereHas('roles', fn($q) => $q->where('name', 'Admin'))
            ->count();

        $staff = User::query()
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['Staff', 'Cashier']))
            ->count();

        return response()->json([
            'total_users' => $total,
            'admins' => $admins,
            'staff' => $staff,
            'disabled' => $disabled,
        ]);
    }

    // GET /users?search=&role=&status=&sort=newest|oldest&per_page=10
    public function index(Request $request)
    {
        $search = trim((string)$request->query('search', ''));
        $role = trim((string)$request->query('role', ''));       // Admin/Staff/Cashier
        $status = trim((string)$request->query('status', ''));   // active/disabled
        $sort = trim((string)$request->query('sort', 'newest')); // newest/oldest
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min(100, $perPage));

        $q = User::query()
            ->with('roles:id,name')
            ->select('id','name','email','phone','is_active','last_login_at','created_at');

        if ($search !== '') {
            $s = mb_strtolower($search);

            $q->where(function ($qq) use ($s) {
                $qq->whereRaw('LOWER(name) LIKE ?', ["%{$s}%"])
                ->orWhereRaw('LOWER(email) LIKE ?', ["%{$s}%"])
                ->orWhereRaw("LOWER(COALESCE(phone,'')) LIKE ?", ["%{$s}%"]);
            });
        }


        if ($status !== '') {
            if (strtolower($status) === 'active') $q->where('is_active', true);
            if (strtolower($status) === 'disabled') $q->where('is_active', false);
        }

        if ($role !== '' && strtolower($role) !== 'all') {
            $q->whereHas('roles', fn($qr) => $qr->where('name', $role));
        }

        if (strtolower($sort) === 'oldest') $q->orderBy('created_at', 'asc');
        else $q->orderBy('created_at', 'desc');

        return $q->paginate($perPage);
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
