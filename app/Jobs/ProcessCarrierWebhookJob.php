<?php

namespace App\Jobs;

use App\Services\TrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessCarrierWebhookJob — Processes incoming carrier tracking webhooks asynchronously.
 *
 * This job is dispatched by TrackingController when a carrier webhook arrives.
 * Processing is offloaded from the HTTP request cycle for performance.
 *
 * Retries: 5 times with exponential backoff.
 * Timeout: 60 seconds per attempt.
 */
class ProcessCarrierWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 5;
    public int   $timeout = 60;
    public array $backoff = [10, 30, 60, 120, 300]; // Exponential

    public function __construct(
        public readonly string $carrier,
        public readonly array  $payload,
        public readonly array  $headers,
        public readonly string $sourceIp,
        public readonly string $webhookId,
    ) {}

    public function handle(TrackingService $service): void
    {
        Log::channel('integration')->info("ProcessCarrierWebhookJob: processing {$this->carrier} webhook", [
            'webhook_id' => $this->webhookId,
        ]);

        // Signature: processWebhook(array $payload, array $headers, string $sourceIp, string $carrierCode)
        $service->processWebhook($this->payload, $this->headers, $this->sourceIp, $this->carrier);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessCarrierWebhookJob permanently failed for {$this->carrier}", [
            'webhook_id' => $this->webhookId,
            'error'      => $exception->getMessage(),
        ]);
    }
}
