<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Permission;
use App\Services\ExcelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;


class ImportController extends Controller
{
    private ExcelService $excelService;

    public function __construct(ExcelService $excelService)
    {
        $this->excelService = $excelService;
    }

    public function importUsers(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('import-users')) {
            return response()->json(['message' => 'Permission denied: import-users'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx',
            'import_mode' => 'required|in:append,overwrite',
            'error_handling_mode' => 'required|in:continue,abort,collect',
        ]);

        $file = $request->file('file');
        $path = $file->store('imports');

        $columns = Schema::getColumnListing('users');
        $allowedColumns = ['id', 'username', 'email', 'birthday'];
        $columns = array_intersect($columns, $allowedColumns);

        try {
            $absolutePath = Storage::path($path);
            $normalizedPath = str_replace('/', DIRECTORY_SEPARATOR, $absolutePath);

            Log::info('Attempting to import file from path: ' . $normalizedPath);

            $results = $this->excelService->import(
                $normalizedPath,
                User::class,
                $columns,
                $request->input('import_mode'),
                $request->input('error_handling_mode')
            );

            Storage::delete($path);

            return response()->json([
                'message' => 'Import completed',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Storage::delete($path);
            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function importPermissions(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('import-permissions')) {
            return response()->json(['message' => 'Permission denied: import-permissions'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx',
            'import_mode' => 'required|in:append,overwrite',
            'error_handling_mode' => 'required|in:continue,abort,collect',
        ]);

        $file = $request->file('file');
        $path = $file->store('imports');

        $columns = Schema::getColumnListing('permissions');
        $allowedColumns = ['id', 'name', 'code', 'description'];
        $columns = array_intersect($columns, $allowedColumns);

        try {
            $absolutePath = Storage::path($path);
            $normalizedPath = str_replace('/', DIRECTORY_SEPARATOR, $absolutePath);

            Log::info('Attempting to import file from path: ' . $normalizedPath);

            $results = $this->excelService->import(
                $normalizedPath,
                Permission::class,
                $columns,
                $request->input('import_mode'),
                $request->input('error_handling_mode')
            );

            Storage::delete($path);

            return response()->json([
                'message' => 'Import completed',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Storage::delete($path);
            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
