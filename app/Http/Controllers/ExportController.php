<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Permission;
use App\Services\ExcelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    private ExcelService $excelService;

    public function __construct(ExcelService $excelService)
    {
        $this->excelService = $excelService;
    }

    public function exportUsers(): JsonResponse
    {
        if (!auth()->user()->hasPermission('export-users')) {
            return response()->json(['message' => 'Permission denied: export-users'], 403);
        }

        $users = User::all();
        $columns = [
            'id' => 'ID',
            'username' => 'Username',
            'email' => 'Email',
            'birthday' => 'Birthday',
        ];

        $filename = 'users_' . date('Y-m-d_His') . '.xlsx';
        $path = $this->excelService->export($users, $columns, $filename);

        return response()->json([
            'message' => 'Users exported successfully',
            'file_path' => Storage::url('exports/' . $filename)
        ]);
    }

    public function exportPermissions(): JsonResponse
    {
        if (!auth()->user()->hasPermission('export-permissions')) {
            return response()->json(['message' => 'Permission denied: export-permissions'], 403);
        }

        $permissions = Permission::all();
        $columns = [
            'id' => 'ID',
            'name' => 'Name',
            'code' => 'Code',
            'description' => 'Description',
        ];

        $filename = 'permissions_' . date('Y-m-d_His') . '.xlsx';
        $path = $this->excelService->export($permissions, $columns, $filename);

        return response()->json([
            'message' => 'Permissions exported successfully',
            'file_path' => Storage::url('exports/' . $filename)
        ]);
    }
} 