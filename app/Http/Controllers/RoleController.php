<?php

namespace App\Http\Controllers;

use App\DTO\Role\RoleDTO;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{

    // Тест 1.1
    public function index(): JsonResponse
    {
        $roles = Role::all();
        return response()->json(RoleDTO::collection($roles), 200);
    }

    // Тест 1.2
    public function show(Role $role): JsonResponse
    {
        if ($role->trashed()) {
            return response()->json(['message' => 'Record is soft deleted'], 404);
        }

        return response()->json(RoleDTO::fromModel($role), 200);
    }

    // Тест 1.4
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create($request->validated());
        return response()->json(RoleDTO::fromModel($role), 201);
    }

    // Тест 1.5
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        if ($role->trashed()) {
            return response()->json(['message' => 'Record is soft deleted'], 404);
        }

        $role->update($request->validated());
        return response()->json(RoleDTO::fromModel($role), 200);
    }

    // Тест 1.6
    public function destroy(Role $role): JsonResponse
    {
        if ($role->trashed()) {
            return response()->json(['message' => 'Record is already soft deleted'], 404);
        }

        $role->delete();
        return response()->json(['message' => 'Role soft deleted successfully'], 200);
    }

    // Тест 1.7
    public function restore($id): JsonResponse
    {
        $role = Role::withTrashed()->find($id);

        if (!$role) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        if (!$role->trashed()) {
            return response()->json(['message' => 'Record is not soft deleted'], 404);
        }

        $role->restore();
        return response()->json(RoleDTO::fromModel($role), 200);
    }

    // Тест 1.8
    public function forceDelete($id): JsonResponse
    {
        $role = Role::withTrashed()->find($id);

        if (!$role) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        $role->forceDelete();
        return response()->json(['message' => 'Role permanently deleted successfully'], 200);
    }
//    public function index(): JsonResponse
//    {
//        $roles = Role::all();
//        return response()->json($roles);
//    }
//
//    public function store(StoreRoleRequest $request): JsonResponse
//    {
//        $role = Role::create($request->toDTO()->toArray());
//        return response()->json($role, 201);
//    }
//
//    public function show(Role $role): JsonResponse
//    {
//        return response()->json($role);
//    }
//
//    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
//    {
//        $role->update($request->toDTO()->toArray());
//        return response()->json($role);
//    }
//
//    public function destroy(Role $role): JsonResponse
//    {
//        $this->authorize('delete-role', $role);
//
//        $role->delete();
//        return response()->json(null, 204);
//    }
}
