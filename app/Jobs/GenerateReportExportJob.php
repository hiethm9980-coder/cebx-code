<?php

namespace App\Jobs;

use App\Models\ReportExport;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * GenerateReportExportJob — Generates a report export file asynchronously.
 *
 * Dispatched by ReportService::createExport() after creating the ReportExport record.
 * Handles CSV, Excel, PDF generation without blocking the HTTP request.
 */
class GenerateReportExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 300; // 5 minutes max for large reports

    public function __construct(
        public readonly string $exportId,
    ) {}

    public function handle(ReportService $service): void
    {
        $export = ReportExport::find($this->exportId);

        if (!$export) {
            Log::warning('GenerateReportExportJob: export not found', ['id' => $this->exportId]);
            return;
        }

        if ($export->status === ReportExport::STATUS_COMPLETED) {
            return; // Already done
        }

        $service->processExport($export);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateReportExportJob permanently failed', [
            'export_id' => $this->exportId,
            'error'     => $exception->getMessage(),
        ]);

        ReportExport::where('id', $this->exportId)->update([
            'status'        => ReportExport::STATUS_FAILED,
            'error_message' => $exception->getMessage(),
        ]);
    }
}
