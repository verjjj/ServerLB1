<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChangeLogController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:get-list-roles');
});

//Route::apiResource('/roles', RoleController::class)->middleware(['auth:sanctum', 'permission:manage-roles']);
Route::apiResource('/permissions', PermissionController::class);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('guest');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/out', [AuthController::class, 'logout']);
    Route::get('/auth/tokens', [AuthController::class, 'tokens']);
    Route::post('/auth/out_all', [AuthController::class, 'logoutAll']);
    Route::post('/auth/change_password', [AuthController::class, 'changePassword']);
});

Route::middleware(['auth:sanctum', 'permission:no-permissions'])->group(function () {
    Route::get('/restricted', [PermissionController::class, 'restricted']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/permissions/{id}/restore', [PermissionController::class, 'restore'])
        ->middleware('permission:restore-permission');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/permissions/{id}/force-delete', [PermissionController::class, 'forceDelete'])
        ->middleware('permission:force-delete-permission');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::post('/users/{id}/restore', [UserController::class, 'restore']);
    Route::get('/users/{id}/history', [ChangeLogController::class, 'getHistory']);
    Route::post('/logs/{id}/rollback', [ChangeLogController::class, 'rollback']);
});
