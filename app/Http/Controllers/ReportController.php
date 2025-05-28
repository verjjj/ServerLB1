<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessReports;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
//use App\Services\ReportService;

class ReportController extends Controller
{
    public function generateAndSendReport(Request $request): JsonResponse
    {
        if (!$this->validateAdminAccess()) {
            return $this->unauthorizedResponse();
        }

        Log::info('Report generation started (dispatching job)');

        ProcessReports::dispatch();

        return response()->json(['message' => 'Report generation started'], 200);
    }

    private function validateAdminAccess(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('admin-access');
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Access denied: Only administrators can perform this action'
        ], 403);
    }
}
