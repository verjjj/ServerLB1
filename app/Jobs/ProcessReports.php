<?php

namespace App\Jobs;

use App\Mail\ReportMail;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessReports implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const REPORT_INTERVAL_HOURS = 24;
    private const ADMIN_EMAIL = 'sherifvd@yandex.ru';


    public function handle(): void
    {
        Log::info('Report generation started');

        try {
            $reportService2 = new ReportService();
            $reportData = $reportService2->generateReportData(
                Carbon::now()->subHours(self::REPORT_INTERVAL_HOURS)
            );

            $fileName = 'report_' . now()->format('Ymd_His') . '.xlsx';
            $filePath = storage_path('app/reports/' . $fileName);

            $reportService2->generateAndSaveReport($reportData, $filePath);
            $this->sendReportToAdmin($filePath, $fileName);

            $this->cleanupReportFile($filePath);

            Log::info('Report generation completed');
        } catch (\Exception $e) {
            Log::error('Error during report generation: ' . $e->getMessage());
        }
    }

    private function sendReportToAdmin(string $filePath, string $fileName): void
    {
        if (!file_exists($filePath)) {
            Log::error("File not found: $filePath");
            return;
        }

        try {
            Log::info('Configured mailer: ' . config('mail.default'));
            Mail::to(self::ADMIN_EMAIL)->send(new ReportMail($filePath, $fileName));
            Log::info("Report sent to " . self::ADMIN_EMAIL);
        } catch (\Exception $e) {
            Log::error("Error sending report: " . $e->getMessage());
            throw $e;
        }
    }

    private function cleanupReportFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
