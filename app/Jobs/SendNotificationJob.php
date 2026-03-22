<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendNotificationJob — Dispatches a single notification asynchronously.
 *
 * Usage:
 *   SendNotificationJob::dispatch($notification, $channel, $destination);
 *
 * Retries: 3 times with 60s backoff.
 * Timeout: 30 seconds per attempt.
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;
    public int $backoff = 60;

    public function __construct(
        public readonly string $notificationId,
        public readonly string $channel,
        public readonly string $destination,
    ) {}

    public function handle(NotificationService $service): void
    {
        $notification = Notification::find($this->notificationId);

        if (!$notification) {
            Log::warning('SendNotificationJob: notification not found', ['id' => $this->notificationId]);
            return;
        }

        // Don't re-send already sent notifications
        if ($notification->status === 'sent') {
            return;
        }

        // sendToChannel() is private and takes (User, Account, eventType, ...).
        // Public entry point for already-created Notification records is sendNotification().
        $service->sendNotification($notification);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendNotificationJob permanently failed', [
            'notification_id' => $this->notificationId,
            'channel'         => $this->channel,
            'error'           => $exception->getMessage(),
        ]);

        // Mark notification as failed
        Notification::where('id', $this->notificationId)->update(['status' => 'failed']);
    }
}
