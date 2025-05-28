<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChangeLogController;
use App\Http\Controllers\TwoFactorAuthController;
use App\Http\Controllers\GitWebhookController;
use App\Http\Controllers\Api\LogRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\FileController;

Route::post('/hooks/git', [GitWebhookController::class, 'handle'])->name('hooks.git');

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('guest');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/out', [AuthController::class, 'logout']);
    Route::get('/auth/tokens', [AuthController::class, 'tokens']);
    Route::post('/auth/out_all', [AuthController::class, 'logoutAll']);
    Route::post('/auth/change_password', [AuthController::class, 'changePassword']);
    
    // File routes
    Route::post('/photos', [FileController::class, 'uploadPhoto']);
    Route::delete('/photos', [FileController::class, 'deletePhoto']);
    Route::get('/photos/{file}/download', [FileController::class, 'downloadPhoto']);
    Route::get('/photos/{file}/avatar', [FileController::class, 'downloadAvatar']);
    
    // Admin only routes
    Route::middleware('admin')->group(function () {
        Route::get('/photos/archive', [FileController::class, 'downloadPhotosArchive']);
    });
});

Route::post('/login-with-role', [AuthController::class, 'loginWithRole']);

Route::middleware(['auth:sanctum', 'permissions:get-list-roles'])->group(function () {
    Route::get('/roles', [RoleController::class, 'index']);
});

// Route::apiResource('/roles', RoleController::class)->middleware(['auth:sanctum', 'permissions:manage-roles']);

Route::apiResource('/permissions', PermissionController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/permissions/{id}/restore', [PermissionController::class, 'restore'])
        ->middleware('permissions:restore-permission');

    Route::delete('/permissions/{id}/force-delete', [PermissionController::class, 'forceDelete'])
        ->middleware('permissions:force-delete-permission');
});

Route::middleware(['auth:sanctum', 'permissions:no-permissions'])->group(function () {
    Route::get('/restricted', [PermissionController::class, 'restricted']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/permissions/{id}/history', [ChangeLogController::class, 'getPermissionHistory']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::delete('/users/{id}/soft', [UserController::class, 'softDelete']);
    Route::post('/users/{id}/restore', [UserController::class, 'restore']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logs/{id}/rollback', [ChangeLogController::class, 'restoreEntityState'])->middleware(['auth:sanctum', 'permissions:restore-entity-state']);
    Route::post('/logs/{id}/rollback', [ChangeLogController::class, 'restoreEntityState']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/2fa/status', [TwoFactorAuthController::class, 'getStatus']);
    Route::post('/2fa/request-code', [TwoFactorAuthController::class, 'requestCode']);
    Route::post('/2fa/verify', [TwoFactorAuthController::class, 'verifyCode']);
    Route::post('/2fa/toggle', [TwoFactorAuthController::class, 'toggleTwoFactor']);
});

Route::middleware(['auth:sanctum', 'permissions:view-logs'])->group(function () {
    Route::get('/logs-requests', [LogRequestController::class, 'index']);
    Route::get('/logs-requests/{id}', [LogRequestController::class, 'show']);
});

//Route::middleware('auth:sanctum')->group(function () {
    Route::post('/report/generate', [ReportController::class, 'generateAndSendReport'])->name('report.generate');
//});


