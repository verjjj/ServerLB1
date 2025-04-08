<?php

namespace App\Http\Controllers;

use App\DTO\User\UserDTO;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::with(['roles', 'permissions'])->get();
        return response()->json($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->toDTO()->toArray());

        if ($request->has('roles')) {
            $user->roles()->attach($request->input('roles'));
        }

        if ($request->has('permissions')) {
            $user->permissions()->attach($request->input('permissions'));
        }

        return response()->json($user->load(['roles', 'permissions']), 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user->load(['roles', 'permissions']));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->update($request->toDTO()->toArray());

        if ($request->has('roles')) {
            $user->roles()->sync($request->input('roles'));
        }

        if ($request->has('permissions')) {
            $user->permissions()->sync($request->input('permissions'));
        }

        return response()->json($user->fresh()->load(['roles', 'permissions']));
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete-user', $user);
        $user->delete();
        return response()->json(null, 204);
    }

    public function attachRoles(User $user): JsonResponse
    {
        // Реализация прикрепления ролей
    }

    public function detachRoles(User $user): JsonResponse
    {
         // Реализация открепления ролей
    }
}
