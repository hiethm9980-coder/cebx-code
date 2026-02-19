<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * NotificationController — FR-NTF-001→009
 *
 * GET    /notifications                    — FR-NTF-008: Notification log
 * GET    /notifications/in-app             — FR-NTF-001: In-app notifications
 * GET    /notifications/unread-count       — FR-NTF-001: Unread count
 * POST   /notifications/{id}/read          — FR-NTF-001: Mark as read
 * POST   /notifications/read-all           — FR-NTF-001: Mark all read
 * GET    /notifications/preferences        — FR-NTF-003: Get preferences
 * PUT    /notifications/preferences        — FR-NTF-003: Update preferences
 * GET    /notifications/templates          — FR-NTF-004: List templates
 * POST   /notifications/templates          — FR-NTF-004: Create template
 * PUT    /notifications/templates/{id}     — FR-NTF-004: Update template
 * POST   /notifications/templates/{id}/preview — FR-NTF-004: Preview
 * GET    /notifications/channels           — FR-NTF-009: List channels
 * POST   /notifications/channels           — FR-NTF-009: Configure channel
 * POST   /notifications/test               — FR-NTF-002: Test send
 * POST   /notifications/schedules          — FR-NTF-007: Create schedule
 * GET    /notifications/schedules          — FR-NTF-007: List schedules
 */
class NotificationController extends Controller
{
    public function __construct(private NotificationService $service) {}

    /**
     * FR-NTF-008: Get notification log.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'  => 'nullable|uuid',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $data = $this->service->getLog(
            $request->user()->account,
            $request->input('user_id'),
            $request->input('per_page', 20)
        );

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    /**
     * FR-NTF-001: Get in-app notifications for current user.
     */
    public function inApp(Request $request): JsonResponse
    {
        $data = $this->service->getInAppNotifications($request->user());

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    /**
     * FR-NTF-001: Get unread count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => ['unread_count' => $this->service->getUnreadCount($request->user())],
        ]);
    }

    /**
     * FR-NTF-001: Mark notification as read.
     */
    public function markRead(Request $request, string $notificationId): JsonResponse
    {
        $this->service->markAsRead($notificationId, $request->user());
        return response()->json(['status' => 'success']);
    }

    /**
     * FR-NTF-001: Mark all notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->service->markAllAsRead($request->user());
        return response()->json(['status' => 'success', 'data' => ['marked' => $count]]);
    }

    /**
     * FR-NTF-003: Get user preferences.
     */
    public function getPreferences(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => $this->service->getPreferences($request->user()),
        ]);
    }

    /**
     * FR-NTF-003: Update user preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'preferences'              => 'required|array|min:1',
            'preferences.*.event_type' => 'required|string',
            'preferences.*.channel'    => 'required|string',
            'preferences.*.enabled'    => 'required|boolean',
            'preferences.*.language'   => 'nullable|string|max:5',
            'preferences.*.destination' => 'nullable|string|max:500',
        ]);

        $this->service->updatePreferences($request->user(), $data['preferences']);

        return response()->json(['status' => 'success', 'message' => 'Preferences updated']);
    }

    /**
     * FR-NTF-004: List templates.
     */
    public function listTemplates(Request $request): JsonResponse
    {
        $templates = $this->service->listTemplates(
            $request->user()->account_id,
            $request->input('event_type')
        );

        return response()->json(['status' => 'success', 'data' => $templates]);
    }

    /**
     * FR-NTF-004: Create template.
     */
    public function createTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_type'   => 'required|string|max:100',
            'channel'      => 'required|string|max:50',
            'language'     => 'required|string|max:5',
            'subject'      => 'nullable|string|max:500',
            'body'         => 'required|string',
            'body_html'    => 'nullable|string',
            'sender_name'  => 'nullable|string|max:200',
            'sender_email' => 'nullable|email|max:200',
            'variables'    => 'nullable|array',
        ]);

        $template = $this->service->createTemplate($data, $request->user()->account_id);

        return response()->json(['status' => 'success', 'data' => $template], 201);
    }

    /**
     * FR-NTF-004: Update template.
     */
    public function updateTemplate(Request $request, string $templateId): JsonResponse
    {
        $data = $request->validate([
            'subject'   => 'nullable|string|max:500',
            'body'      => 'nullable|string',
            'body_html' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $template = $this->service->updateTemplate($templateId, $data);

        return response()->json(['status' => 'success', 'data' => $template]);
    }

    /**
     * FR-NTF-004: Preview template.
     */
    public function previewTemplate(Request $request, string $templateId): JsonResponse
    {
        $data = $request->validate(['sample_data' => 'required|array']);
        $rendered = $this->service->previewTemplate($templateId, $data['sample_data']);

        return response()->json(['status' => 'success', 'data' => $rendered]);
    }

    /**
     * FR-NTF-009: List configured channels.
     */
    public function listChannels(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => $this->service->listChannels($request->user()->account),
        ]);
    }

    /**
     * FR-NTF-009: Configure a notification channel.
     */
    public function configureChannel(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel'        => 'required|string|max:50',
            'provider'       => 'required|string|max:100',
            'name'           => 'required|string|max:200',
            'config'         => 'nullable|array',
            'webhook_url'    => 'nullable|url|max:500',
            'webhook_secret' => 'nullable|string|max:500',
            'is_active'      => 'nullable|boolean',
        ]);

        $channel = $this->service->configureChannel($request->user()->account, $data);

        return response()->json(['status' => 'success', 'data' => $channel], 201);
    }

    /**
     * FR-NTF-002: Test send notification.
     */
    public function testSend(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_type'  => 'required|string',
            'channel'     => 'required|string',
            'destination' => 'required|string',
        ]);

        $results = $this->service->dispatch(
            $data['event_type'],
            $request->user()->account,
            ['test' => true, 'timestamp' => now()->toIso8601String()],
            'test', 'test-001',
            [$request->user()->id]
        );

        return response()->json([
            'status' => 'success',
            'data'   => ['sent' => count($results)],
        ]);
    }

    /**
     * FR-NTF-007: Create notification schedule.
     */
    public function createSchedule(Request $request): JsonResponse
    {
        $data = $request->validate([
            'frequency'   => 'required|in:immediate,hourly,daily,weekly',
            'time_of_day' => 'nullable|date_format:H:i',
            'day_of_week' => 'nullable|string',
            'timezone'    => 'nullable|string|max:50',
            'event_types' => 'nullable|array',
            'channel'     => 'required|string|max:50',
        ]);

        $schedule = \App\Models\NotificationSchedule::create(array_merge($data, [
            'account_id' => $request->user()->account_id,
            'user_id'    => $request->user()->id,
            'is_active'  => true,
        ]));

        if ($schedule->frequency !== 'immediate') {
            $schedule->calculateNextSend();
        }

        return response()->json(['status' => 'success', 'data' => $schedule], 201);
    }

    /**
     * FR-NTF-007: List user schedules.
     */
    public function listSchedules(Request $request): JsonResponse
    {
        $schedules = \App\Models\NotificationSchedule::where('user_id', $request->user()->id)->get();
        return response()->json(['status' => 'success', 'data' => $schedules]);
    }
}
