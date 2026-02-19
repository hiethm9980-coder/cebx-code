<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Account;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\StatusMapping;
use App\Models\TrackingEvent;
use App\Models\TrackingSubscription;
use App\Models\TrackingWebhook;
use App\Models\User;
use App\Services\Carriers\DhlApiService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * TrackingService — FR-TR-001→007 (7 requirements)
 *
 * FR-TR-001: Receive tracking events (Webhooks/Polling)
 * FR-TR-002: Verify webhook authenticity (signature, schema, replay)
 * FR-TR-003: Deduplication & out-of-order handling
 * FR-TR-004: Normalize carrier status → unified status + notify subscribers
 * FR-TR-005: Timeline display with event details
 * FR-TR-006: Update store with tracking status changes
 * FR-TR-007: Exception management (Requires Action)
 */
class TrackingService
{
    public function __construct(
        private DhlApiService $dhlApi,
        private AuditService $audit,
    ) {}

    // ═══════════════════════════════════════════════════════════
    // FR-TR-001 + FR-TR-002: Receive & Verify Webhook
    // ═══════════════════════════════════════════════════════════

    /**
     * Process an inbound DHL tracking webhook.
     * Verifies, normalizes, deduplicates, and stores events.
     */
    public function processWebhook(
        array $payload,
        array $headers,
        string $sourceIp
    ): array {
        $startTime = microtime(true);

        // ── Log the webhook ──────────────────────────────────
        $webhook = TrackingWebhook::create([
            'carrier_code'      => 'dhl',
            'signature'         => $headers['x-dhl-signature'] ?? $headers['authorization'] ?? null,
            'message_reference' => $headers['message-reference'] ?? null,
            'replay_token'      => $headers['x-dhl-message-id'] ?? $payload['messageId'] ?? Str::uuid()->toString(),
            'source_ip'         => $sourceIp,
            'user_agent'        => $headers['user-agent'] ?? null,
            'headers'           => $headers,
            'event_type'        => $payload['eventType'] ?? $payload['type'] ?? 'tracking_update',
            'tracking_number'   => $this->extractTrackingNumber($payload),
            'payload'           => $payload,
            'payload_size'      => strlen(json_encode($payload)),
        ]);

        // ── FR-TR-002: Verify signature ──────────────────────
        if (!$this->verifyWebhookSignature($headers, $payload)) {
            $webhook->markRejected('Invalid webhook signature');
            return ['status' => 'rejected', 'reason' => 'invalid_signature', 'events' => 0];
        }

        // ── FR-TR-002: Prevent replay ────────────────────────
        $existingReplay = TrackingWebhook::where('replay_token', $webhook->replay_token)
            ->where('id', '!=', $webhook->id)
            ->whereIn('status', ['validated', 'processed'])
            ->exists();

        if ($existingReplay) {
            $webhook->markRejected('Replay detected');
            return ['status' => 'rejected', 'reason' => 'replay_detected', 'events' => 0];
        }

        // ── FR-TR-002: Validate schema ───────────────────────
        if (!$this->validateWebhookSchema($payload)) {
            $webhook->markRejected('Invalid payload schema');
            return ['status' => 'rejected', 'reason' => 'invalid_schema', 'events' => 0];
        }

        $webhook->markValidated();

        // ── Extract and process events ───────────────────────
        $rawEvents = $this->extractEventsFromPayload($payload);
        $processed = 0;

        foreach ($rawEvents as $rawEvent) {
            $result = $this->processTrackingEvent($rawEvent, 'webhook', $webhook->id);
            if ($result) $processed++;
        }

        $processingMs = (int) ((microtime(true) - $startTime) * 1000);
        $webhook->markProcessed($processed, $processingMs);

        return ['status' => 'processed', 'events' => $processed, 'webhook_id' => $webhook->id];
    }

    // ═══════════════════════════════════════════════════════════
    // FR-TR-001: Polling Fallback
    // ═══════════════════════════════════════════════════════════

    /**
     * Poll DHL for tracking updates on active shipments.
     */
    public function pollTrackingUpdates(array $trackingNumbers): array
    {
        $results = ['polled' => 0, 'new_events' => 0, 'errors' => 0];

        foreach ($trackingNumbers as $trackingNumber) {
            try {
                $response = $this->dhlApi->trackShipment($trackingNumber);
                $events = $this->extractEventsFromTrackResponse($response, $trackingNumber);

                foreach ($events as $rawEvent) {
                    $result = $this->processTrackingEvent($rawEvent, 'polling');
                    if ($result) $results['new_events']++;
                }

                $results['polled']++;
            } catch (\Exception $e) {
                $results['errors']++;
            }
        }

        return $results;
    }

    // ═══════════════════════════════════════════════════════════
    // FR-TR-003 + FR-TR-004: Process & Normalize Event
    // ═══════════════════════════════════════════════════════════

    /**
     * Process a single tracking event: dedup, normalize, store, notify.
     */
    private function processTrackingEvent(array $raw, string $source, ?string $webhookId = null): ?TrackingEvent
    {
        $trackingNumber = $raw['tracking_number'];
        $eventTime = $raw['event_time'];
        $rawStatus = $raw['raw_status'];
        $locationCode = $raw['location_code'] ?? null;

        // ── FR-TR-003: Deduplication ─────────────────────────
        $dedupKey = TrackingEvent::generateDedupKey($trackingNumber, $rawStatus, $eventTime, $locationCode);
        $exists = TrackingEvent::where('dedup_key', $dedupKey)->exists();
        if ($exists) {
            return null; // Duplicate, skip
        }

        // ── Find shipment ────────────────────────────────────
        $shipment = Shipment::where('tracking_number', $trackingNumber)->first();
        if (!$shipment) {
            return null; // No matching shipment
        }

        // ── FR-TR-004: Normalize status ──────────────────────
        $mapping = StatusMapping::resolve('dhl', $rawStatus, $raw['raw_status_code'] ?? null);
        $unifiedStatus = $mapping?->unified_status ?? TrackingEvent::STATUS_UNKNOWN;
        $unifiedDescription = $mapping?->unified_description ?? $raw['raw_description'] ?? $rawStatus;
        $isException = $mapping?->is_exception ?? false;
        $requiresAction = $mapping?->requires_action ?? false;

        // ── FR-TR-003: Out-of-order check ────────────────────
        $latestEvent = TrackingEvent::where('shipment_id', $shipment->id)
            ->orderBy('event_time', 'desc')
            ->first();

        $sequenceNumber = ($latestEvent?->sequence_number ?? 0) + 1;

        // ── Store event ──────────────────────────────────────
        $event = TrackingEvent::create([
            'shipment_id'         => $shipment->id,
            'account_id'          => $shipment->account_id,
            'carrier_code'        => 'dhl',
            'tracking_number'     => $trackingNumber,
            'raw_status'          => $rawStatus,
            'raw_description'     => $raw['raw_description'] ?? null,
            'raw_status_code'     => $raw['raw_status_code'] ?? null,
            'unified_status'      => $unifiedStatus,
            'unified_description' => $unifiedDescription,
            'event_time'          => $eventTime,
            'location_city'       => $raw['location_city'] ?? null,
            'location_country'    => $raw['location_country'] ?? null,
            'location_code'       => $locationCode,
            'location_description' => $raw['location_description'] ?? null,
            'signatory'           => $raw['signatory'] ?? null,
            'source'              => $source,
            'dedup_key'           => $dedupKey,
            'sequence_number'     => $sequenceNumber,
            'webhook_id'          => $webhookId,
            'is_exception'        => $isException,
            'raw_payload'         => $raw,
        ]);

        // ── Update shipment status ───────────────────────────
        $this->updateShipmentStatus($shipment, $event, $latestEvent);

        // ── FR-TR-006: Update store if needed ────────────────
        if ($mapping?->notify_store) {
            $this->notifyStore($shipment, $event, $mapping);
            $event->update(['notified_store' => true]);
        }

        // ── FR-TR-004: Notify subscribers ────────────────────
        $this->notifySubscribers($shipment, $event);
        $event->update(['notified_subscribers' => true]);

        // ── FR-TR-007: Create exception if needed ────────────
        if ($isException || $requiresAction) {
            $this->createException($event, $raw);
        }

        return $event;
    }

    /**
     * Update shipment's unified status (only forward progression).
     */
    private function updateShipmentStatus(Shipment $shipment, TrackingEvent $newEvent, ?TrackingEvent $latestEvent): void
    {
        // Only update if this is the most recent event
        if ($latestEvent && $newEvent->event_time < $latestEvent->event_time) {
            return; // Out-of-order, don't update main status
        }

        $shipment->update([
            'tracking_status'      => $newEvent->unified_status,
            'tracking_updated_at'  => $newEvent->event_time,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // FR-TR-005: Timeline
    // ═══════════════════════════════════════════════════════════

    /**
     * Get tracking timeline for a shipment.
     */
    public function getTimeline(Shipment $shipment): array
    {
        $events = TrackingEvent::timeline($shipment->id)->get();

        return [
            'shipment_id'       => $shipment->id,
            'tracking_number'   => $shipment->tracking_number,
            'current_status'    => $shipment->tracking_status ?? $shipment->status,
            'last_updated'      => $shipment->tracking_updated_at,
            'total_events'      => $events->count(),
            'events'            => $events->map->toTimeline()->values()->toArray(),
        ];
    }

    /**
     * Search/filter shipments by status (FR-TR-005 companion).
     */
    public function searchByStatus(
        Account $account,
        ?string $status = null,
        ?string $trackingNumber = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 20
    ) {
        $query = Shipment::where('account_id', $account->id);

        if ($status) {
            $query->where('tracking_status', $status);
        }
        if ($trackingNumber) {
            $query->where('tracking_number', 'like', "%{$trackingNumber}%");
        }
        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        return $query->orderBy('tracking_updated_at', 'desc')->paginate($perPage);
    }

    // ═══════════════════════════════════════════════════════════
    // FR-TR-006: Store Status Update
    // ═══════════════════════════════════════════════════════════

    /**
     * Send status update to the linked store.
     */
    private function notifyStore(Shipment $shipment, TrackingEvent $event, StatusMapping $mapping): void
    {
        if (!$shipment->store_id || !$mapping->store_status) {
            return;
        }

        // Queue store update (would be dispatched as a job in production)
        // StoreStatusUpdateJob::dispatch($shipment, $mapping->store_status, $event->toTimeline());
    }

    // ═══════════════════════════════════════════════════════════
    // FR-TR-004: Notify Subscribers
    // ═══════════════════════════════════════════════════════════

    /**
     * Notify all subscribers of a shipment about a status change.
     */
    private function notifySubscribers(Shipment $shipment, TrackingEvent $event): void
    {
        $subscriptions = TrackingSubscription::forShipment($shipment->id)->get();

        foreach ($subscriptions as $sub) {
            if ($sub->wantsEvent($event->unified_status)) {
                // Would dispatch notification job per channel
                $sub->recordNotification();
            }
        }
    }

    // ═══════════════════════════════════════════════════════════
    // FR-TR-007: Exception Management
    // ═══════════════════════════════════════════════════════════

    /**
     * Create an exception record from a tracking event.
     */
    private function createException(TrackingEvent $event, array $rawData): ShipmentException
    {
        $exceptionCode = $this->classifyException($rawData);
        return ShipmentException::fromTrackingEvent($event, $exceptionCode, $rawData['raw_description'] ?? null);
    }

    /**
     * Classify exception type from raw carrier data.
     */
    private function classifyException(array $raw): string
    {
        $desc = strtolower($raw['raw_description'] ?? '');
        $code = strtolower($raw['raw_status_code'] ?? '');

        if (str_contains($desc, 'address') || str_contains($code, 'address')) return 'ADDRESS_ISSUE';
        if (str_contains($desc, 'customs') || str_contains($code, 'customs')) return 'CUSTOMS_HOLD';
        if (str_contains($desc, 'damage') || str_contains($code, 'damage')) return 'DAMAGED_PACKAGE';
        if (str_contains($desc, 'refused') || str_contains($desc, 'reject')) return 'REFUSED_BY_RECIPIENT';
        if (str_contains($desc, 'delivery') && str_contains($desc, 'fail')) return 'DELIVERY_FAILED';
        if (str_contains($desc, 'document') || str_contains($desc, 'missing')) return 'MISSING_DOCUMENTATION';
        if (str_contains($desc, 'security')) return 'SECURITY_HOLD';
        if (str_contains($desc, 'weather')) return 'WEATHER_DELAY';

        return 'OTHER';
    }

    /**
     * Get exceptions for a shipment (FR-TR-007).
     */
    public function getExceptions(Shipment $shipment): Collection
    {
        return ShipmentException::where('shipment_id', $shipment->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Acknowledge an exception (FR-TR-007).
     */
    public function acknowledgeException(string $exceptionId, User $user): ShipmentException
    {
        $exception = ShipmentException::findOrFail($exceptionId);
        $exception->acknowledge();

        $this->audit->log('tracking.exception_acknowledged', $user, $exception, [
            'exception_code' => $exception->exception_code,
        ]);

        return $exception;
    }

    /**
     * Resolve an exception (FR-TR-007).
     */
    public function resolveException(string $exceptionId, string $notes, User $user): ShipmentException
    {
        $exception = ShipmentException::findOrFail($exceptionId);
        $exception->resolve($notes, $user->name ?? $user->email);

        $this->audit->log('tracking.exception_resolved', $user, $exception, [
            'exception_code' => $exception->exception_code,
        ]);

        return $exception;
    }

    // ═══════════════════════════════════════════════════════════
    // FR-TR-004: Subscription Management
    // ═══════════════════════════════════════════════════════════

    public function subscribe(array $data, Shipment $shipment, User $user): TrackingSubscription
    {
        return TrackingSubscription::create([
            'shipment_id'     => $shipment->id,
            'account_id'      => $shipment->account_id,
            'channel'         => $data['channel'],
            'destination'     => $data['destination'],
            'subscriber_name' => $data['subscriber_name'] ?? $user->name,
            'event_types'     => $data['event_types'] ?? null,
            'language'         => $data['language'] ?? 'ar',
        ]);
    }

    public function unsubscribe(string $subscriptionId): void
    {
        TrackingSubscription::where('id', $subscriptionId)->update(['is_active' => false]);
    }

    // ═══════════════════════════════════════════════════════════
    // FR-TR-006: Dashboard Stats
    // ═══════════════════════════════════════════════════════════

    /**
     * Get shipment status summary for dashboard.
     */
    public function getStatusDashboard(Account $account, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = Shipment::where('account_id', $account->id);

        if ($dateFrom) $query->where('created_at', '>=', $dateFrom);
        if ($dateTo) $query->where('created_at', '<=', $dateTo);

        $counts = (clone $query)->selectRaw('tracking_status, COUNT(*) as total')
            ->groupBy('tracking_status')
            ->pluck('total', 'tracking_status')
            ->toArray();

        $total = array_sum($counts);
        $exceptions = ShipmentException::where('account_id', $account->id)->open()->count();

        return [
            'total_shipments' => $total,
            'by_status'       => $counts,
            'open_exceptions' => $exceptions,
            'delivery_rate'   => $total > 0 ? round(($counts[TrackingEvent::STATUS_DELIVERED] ?? 0) / $total * 100, 1) : 0,
            'exception_rate'  => $total > 0 ? round($exceptions / $total * 100, 1) : 0,
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // FR-TR-002 + Helpers
    // ═══════════════════════════════════════════════════════════

    private function verifyWebhookSignature(array $headers, array $payload): bool
    {
        $signature = $headers['x-dhl-signature'] ?? $headers['authorization'] ?? null;
        if (!$signature) return false;

        $secret = config('services.dhl.webhook_secret', '');
        if (empty($secret)) return true; // No secret configured = skip verification

        $expected = hash_hmac('sha256', json_encode($payload), $secret);
        return hash_equals($expected, $signature);
    }

    private function validateWebhookSchema(array $payload): bool
    {
        // Must have at minimum tracking data
        return !empty($payload['trackingNumber'] ?? $payload['shipmentTrackingNumber'] ?? $payload['events'] ?? null);
    }

    private function extractTrackingNumber(array $payload): ?string
    {
        return $payload['trackingNumber']
            ?? $payload['shipmentTrackingNumber']
            ?? $payload['awbNumber']
            ?? ($payload['events'][0]['trackingNumber'] ?? null);
    }

    private function extractEventsFromPayload(array $payload): array
    {
        $events = [];
        $trackingNumber = $this->extractTrackingNumber($payload);

        $rawEvents = $payload['events'] ?? $payload['shipmentEvents'] ?? [$payload];

        foreach ($rawEvents as $raw) {
            $events[] = [
                'tracking_number'      => $raw['trackingNumber'] ?? $trackingNumber,
                'raw_status'           => $raw['status'] ?? $raw['description'] ?? 'unknown',
                'raw_description'      => $raw['description'] ?? $raw['statusDescription'] ?? null,
                'raw_status_code'      => $raw['statusCode'] ?? $raw['typeCode'] ?? null,
                'event_time'           => $raw['timestamp'] ?? $raw['date'] ?? now()->toIso8601String(),
                'location_city'        => $raw['location']['address']['addressLocality'] ?? $raw['city'] ?? null,
                'location_country'     => $raw['location']['address']['countryCode'] ?? $raw['countryCode'] ?? null,
                'location_code'        => $raw['location']['servicePoint']['url'] ?? $raw['facilityCode'] ?? null,
                'location_description' => $raw['location']['description'] ?? null,
                'signatory'            => $raw['signedBy'] ?? $raw['signatory'] ?? null,
            ];
        }

        return $events;
    }

    private function extractEventsFromTrackResponse(array $response, string $trackingNumber): array
    {
        $events = [];
        $shipments = $response['shipments'] ?? [$response];

        foreach ($shipments as $shipment) {
            foreach ($shipment['events'] ?? [] as $raw) {
                $events[] = [
                    'tracking_number'  => $trackingNumber,
                    'raw_status'       => $raw['status'] ?? $raw['description'] ?? 'unknown',
                    'raw_description'  => $raw['description'] ?? null,
                    'raw_status_code'  => $raw['statusCode'] ?? $raw['typeCode'] ?? null,
                    'event_time'       => $raw['timestamp'] ?? $raw['date'] ?? now()->toIso8601String(),
                    'location_city'    => $raw['location']['address']['addressLocality'] ?? null,
                    'location_country' => $raw['location']['address']['countryCode'] ?? null,
                    'location_code'    => $raw['location']['servicePoint']['url'] ?? null,
                    'signatory'        => $raw['signedBy'] ?? null,
                ];
            }
        }

        return $events;
    }
}
