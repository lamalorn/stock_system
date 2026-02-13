<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Http\Requests\SyncRolePermissionsRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        return Role::query()->where('guard_name', 'api')->with('permissions')->orderBy('name')->get();
    }

    public function store(RoleStoreRequest $request)
    {
        $data = $request->validated();

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'api']);
        return response()->json($role, 201);
    }

    public function update(RoleUpdateRequest $request, Role $role)
    {
        $data = $request->validated();

        $role->update(['name' => $data['name']]);
        return response()->json(['message' => 'Updated', 'role' => $role]);
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role)
    {
        $data = $request->validated();

        $role->syncPermissions($data['permissions']);
        return response()->json(['message' => 'Synced', 'role' => $role->load('permissions')]);
    }
}
