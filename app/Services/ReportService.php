<?php

namespace App\Services;

use App\Models\ChangeLog;
use App\Models\LogRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ReportService
{
    public function generateReportData(Carbon $startTime): array
    {
        return [
            'type' => 'System Report',
            'generated_at' => now()->toDateTimeString(),
            'method_ratings' => $this->getMethodRatings($startTime),
            'entity_ratings' => $this->getEntityRatings($startTime),
            'user_ratings' => $this->getUserRatings($startTime),
        ];
    }

    public function generateAndSaveReport(array $data, string $filePath): void
    {
        Storage::makeDirectory('reports');
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Type: ' . $data['type']);
        $sheet->setCellValue('A2', 'Generated At: ' . $data['generated_at']);

        $row = $this->writeSection($sheet, 'Method Ratings', ['Method', 'Count', 'Last Operation'], $data['method_ratings'], 4);
        $row = $this->writeSection($sheet, 'Entity Ratings', ['Entity', 'Count', 'Last Operation'], $data['entity_ratings'], $row + 2);
        $this->writeSection($sheet, 'User Ratings', ['User', 'Count', 'Last Operation'], $data['user_ratings'], $row + 2);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($filePath);

        if (file_exists($filePath)) {
            Log::info("File successfully created: $filePath");
        } else {
            Log::error("Failed to create file: $filePath");
            throw new \RuntimeException("Failed to create report file");
        }
    }

    private function getMethodRatings(Carbon $startTime): array
    {
        return LogRequest::where('created_at', '>=', $startTime)
            ->selectRaw('controller_method, COUNT(*) as count')
            ->groupBy('controller_method')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) use ($startTime) {
                return [
                    'method' => $item->controller_method,
                    'count' => $item->count,
                    'last_operation' => LogRequest::where('controller_method', $item->controller_method)
                        ->where('created_at', '>=', $startTime)
                        ->max('created_at'),
                ];
            })
            ->toArray();
    }

    private function getEntityRatings(Carbon $startTime): array
    {
        return ChangeLog::where('created_at', '>=', $startTime)
            ->selectRaw('entity_type, COUNT(*) as count')
            ->groupBy('entity_type')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) use ($startTime) {
                return [
                    'entity' => $item->entity_type,
                    'count' => $item->count,
                    'last_operation' => ChangeLog::where('entity_type', $item->entity_type)
                        ->where('created_at', '>=', $startTime)
                        ->max('created_at'),
                ];
            })
            ->toArray();
    }

    private function getUserRatings(Carbon $startTime): array
    {
        return User::withCount(['logRequests' => function ($query) use ($startTime) {
            $query->where('created_at', '>=', $startTime);
        }])
            ->orderByDesc('log_requests_count')
            ->get()
            ->map(function ($user) use ($startTime) {
                return [
                    'user' => $user->username,
                    'count' => $user->log_requests_count,
                    'last_operation' => LogRequest::where('user_id', $user->id)
                        ->where('created_at', '>=', $startTime)
                        ->max('created_at'),
                ];
            })
            ->toArray();
    }

    private function writeSection($sheet, string $title, array $headers, array $data, int $startRow): int
    {
        $sheet->setCellValue("A$startRow", $title);
        $sheet->fromArray($headers, null, "A" . ($startRow + 1));
        $row = $startRow + 2;

        foreach ($data as $item) {
            $sheet->fromArray([
                $item['method'] ?? $item['entity'] ?? $item['user'],
                $item['count'],
                $item['last_operation']
            ], null, "A$row");
            $row++;
        }

        return $row;
    }
} 