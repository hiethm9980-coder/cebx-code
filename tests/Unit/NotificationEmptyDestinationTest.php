<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Regression tests — Notification empty-destination false-positive.
 *
 * Verifies that webhook/slack channels with no URL configured
 * NEVER produce a notification with status=sent.
 *
 * Also verifies the positive paths: email succeeds, SMS throws on
 * unconfigured Twilio.
 */
class NotificationEmptyDestinationTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;
    private Account $account;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(NotificationService::class);
        $this->account = Account::factory()->create();
        $this->user    = User::factory()->create([
            'account_id' => $this->account->id,
            'email'      => 'user@test.com',
        ]);
    }

    // ─── Positive path ───────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_email_dispatch_results_in_sent_status(): void
    {
        Mail::fake();

        $results = $this->service->dispatch(
            Notification::EVENT_SHIPMENT_DELIVERED,
            $this->account,
            ['tracking_number' => 'TRK-EMAIL'],
            'shipment', 'ship-001',
            [$this->user->id]
        );

        $emailNotification = collect($results)
            ->first(fn($n) => $n->channel === Notification::CHANNEL_EMAIL);

        $this->assertNotNull($emailNotification, 'Email notification should be created');
        $this->assertEquals(
            Notification::STATUS_SENT,
            $emailNotification->fresh()->status,
            'Email notification must have status=sent'
        );
    }

    // ─── Webhook: empty destination ───────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_webhook_with_no_configured_url_creates_no_notification(): void
    {
        // No NotificationChannel record for webhook → resolveDestination returns null
        // → sendToChannel exits before creating any DB record
        $results = $this->service->dispatch(
            Notification::EVENT_SHIPMENT_DELIVERED,
            $this->account,
            [],
            'shipment', 'ship-002',
            [$this->user->id]
        );

        $webhookResults = collect($results)
            ->filter(fn($n) => $n->channel === Notification::CHANNEL_WEBHOOK);

        $this->assertCount(0, $webhookResults, 'No webhook notification should be created when no URL is configured');
        $this->assertDatabaseMissing('notifications', [
            'channel' => Notification::CHANNEL_WEBHOOK,
            'status'  => Notification::STATUS_SENT,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_webhook_with_blank_destination_in_retry_results_in_failed_not_sent(): void
    {
        // Simulate a notification that somehow has a blank destination
        // (e.g., the webhook URL was deleted after the notification was queued)
        $notification = Notification::factory()->create([
            'account_id'  => $this->account->id,
            'user_id'     => $this->user->id,
            'channel'     => Notification::CHANNEL_WEBHOOK,
            'destination' => '',       // blank — simulates deleted URL
            'status'      => Notification::STATUS_RETRYING,
            'retry_count' => 0,
            'max_retries' => 3,
        ]);

        // processRetryQueue calls send() which calls sendWebhook()
        // sendWebhook throws when destination blank → markFailed()
        $this->service->processRetryQueue();

        $fresh = $notification->fresh();
        $this->assertNotEquals(
            Notification::STATUS_SENT,
            $fresh->status,
            'Webhook notification with blank destination must NOT be marked sent'
        );
        $this->assertContains($fresh->status, [
            Notification::STATUS_FAILED,
            Notification::STATUS_RETRYING,
            Notification::STATUS_DLQ,
        ], 'Status must be failed/retrying/dlq — never sent');
    }

    // ─── Slack: empty destination ─────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_slack_with_no_configured_url_creates_no_notification(): void
    {
        $results = $this->service->dispatch(
            Notification::EVENT_SHIPMENT_DELIVERED,
            $this->account,
            [],
            'shipment', 'ship-003',
            [$this->user->id]
        );

        $slackResults = collect($results)
            ->filter(fn($n) => $n->channel === Notification::CHANNEL_SLACK);

        $this->assertCount(0, $slackResults, 'No Slack notification should be created when no URL is configured');
        $this->assertDatabaseMissing('notifications', [
            'channel' => Notification::CHANNEL_SLACK,
            'status'  => Notification::STATUS_SENT,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_slack_with_blank_destination_in_retry_results_in_failed_not_sent(): void
    {
        $notification = Notification::factory()->create([
            'account_id'  => $this->account->id,
            'user_id'     => $this->user->id,
            'channel'     => Notification::CHANNEL_SLACK,
            'destination' => '',
            'status'      => Notification::STATUS_RETRYING,
            'retry_count' => 0,
            'max_retries' => 3,
        ]);

        $this->service->processRetryQueue();

        $fresh = $notification->fresh();
        $this->assertNotEquals(
            Notification::STATUS_SENT,
            $fresh->status,
            'Slack notification with blank destination must NOT be marked sent'
        );
    }

    // ─── SMS: unconfigured Twilio ─────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_sms_with_unconfigured_twilio_results_in_failed_not_sent(): void
    {
        // Ensure Twilio is not configured (phpunit.xml does not set these)
        config(['services.twilio.sid' => null]);
        config(['services.twilio.auth_token' => null]);

        // Force SMS channel by creating a preference
        \App\Models\NotificationPreference::create([
            'user_id'    => $this->user->id,
            'account_id' => $this->account->id,
            'event_type' => Notification::EVENT_SHIPMENT_DELIVERED,
            'channel'    => Notification::CHANNEL_SMS,
            'enabled'    => true,
        ]);

        // User must have a phone for resolveDestination to return non-null
        $this->user->update(['phone' => '+966500000000']);

        $results = $this->service->dispatch(
            Notification::EVENT_SHIPMENT_DELIVERED,
            $this->account,
            [],
            'shipment', 'ship-004',
            [$this->user->id]
        );

        $smsNotification = collect($results)
            ->first(fn($n) => $n->channel === Notification::CHANNEL_SMS);

        if ($smsNotification) {
            $this->assertNotEquals(
                Notification::STATUS_SENT,
                $smsNotification->fresh()->status,
                'SMS with unconfigured Twilio must NOT be marked sent'
            );
        } else {
            // If SMS was filtered out before creation (e.g., preferences), that is also acceptable
            $this->assertDatabaseMissing('notifications', [
                'channel' => Notification::CHANNEL_SMS,
                'status'  => Notification::STATUS_SENT,
            ]);
        }
    }
}
