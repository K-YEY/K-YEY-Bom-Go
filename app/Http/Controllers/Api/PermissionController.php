<?php

namespace App\Http\Controllers\Api;

use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController
{
    public function index(Request $request): JsonResponse
    {
        $permissions = Permission::query()
            ->when($request->filled('group'), fn ($query) => $query->where('group', $request->string('group')->toString()))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->orderBy('group')
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name', 'group', 'label', 'type']);

        return response()->json([
            'data' => $permissions,
            'meta' => [
                'groups' => $permissions->pluck('group')->filter()->unique()->values(),
                'types' => $permissions->pluck('type')->filter()->unique()->values(),
            ],
        ]);
    }

    public function show(Permission $permission): JsonResponse
    {
        $permission->load('roles:id,name,label,is_active');

        return response()->json([
            'data' => [
                'id' => $permission->id,
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
                'group' => $permission->group,
                'label' => $permission->label,
                'type' => $permission->type,
                'roles' => $permission->roles,
            ],
        ]);
    }
}
