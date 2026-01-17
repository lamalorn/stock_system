<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PermissionStoreRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        return Permission::query()->orderBy('name')->get();
    }

    public function store(PermissionStoreRequest $request)
    {
        $data = $request->validated();

        $perm = Permission::create(['name' => $data['name'], 'guard_name' => 'api']);
        return response()->json($perm, 201);
    }

    public function update(PermissionStoreRequest $request, Permission $permission)
    {
        $data = $request->validated();

        $permission->update(['name' => $data['name']]);
        return response()->json(['message' => 'Updated', 'permission' => $permission]);
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
