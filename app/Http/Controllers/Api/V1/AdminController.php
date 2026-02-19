<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AdminController — FR-ADM-001→010
 */
class AdminController extends Controller
{
    public function __construct(private AdminService $service) {}

    // ═══════════════ FR-ADM-001: System Settings ═════════════

    public function getSettings(string $group): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->service->getSettings($group)]);
    }

    public function updateSetting(Request $request): JsonResponse
    {
        $data = $request->validate([
            'group' => 'required|string', 'key' => 'required|string',
            'value' => 'required', 'type' => 'nullable|in:string,integer,boolean,json,encrypted',
        ]);
        $setting = $this->service->updateSetting($data['group'], $data['key'], $data['value'], $data['type'] ?? 'string', $request->user()->id);
        return response()->json(['status' => 'success', 'data' => $setting]);
    }

    public function testCarrierConnection(Request $request): JsonResponse
    {
        $data = $request->validate(['carrier' => 'required|string']);
        return response()->json(['status' => 'success', 'data' => $this->service->testCarrierConnection($data['carrier'])]);
    }

    // ═══════════════ FR-ADM-002/006: Health ══════════════════

    public function integrationHealth(Request $request): JsonResponse
    {
        $data = $this->service->getIntegrationHealth($request->input('service'));
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function systemHealth(): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->service->getSystemHealthDashboard()]);
    }

    // ═══════════════ FR-ADM-003: Users ═══════════════════════

    public function listUsers(Request $request): JsonResponse
    {
        $data = $this->service->listPlatformUsers($request->only('account_id', 'role', 'search'));
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function suspendUser(Request $request, string $userId): JsonResponse
    {
        $data = $request->validate(['reason' => 'required|string']);
        return response()->json(['status' => 'success', 'data' => $this->service->suspendUser($userId, $data['reason'])]);
    }

    public function activateUser(string $userId): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->service->activateUser($userId)]);
    }

    // ═══════════════ FR-ADM-005: Tax Rules ═══════════════════

    public function listTaxRules(Request $request): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->service->listTaxRules($request->input('country'))]);
    }

    public function createTaxRule(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string', 'country_code' => 'required|string|size:2',
            'rate' => 'required|numeric|min:0', 'applies_to' => 'nullable|in:shipping,subscription,all',
        ]);
        return response()->json(['status' => 'success', 'data' => $this->service->createTaxRule($data)], 201);
    }

    // ═══════════════ FR-ADM-006: Role Templates ══════════════

    public function listRoleTemplates(): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->service->listRoleTemplates()]);
    }

    public function createRoleTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string', 'slug' => 'required|string|unique:role_templates',
            'permissions' => 'required|array',
        ]);
        return response()->json(['status' => 'success', 'data' => $this->service->createRoleTemplate($data)], 201);
    }

    // ═══════════════ FR-ADM-008: Support Tickets ═════════════

    public function createTicket(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => 'required|string|max:300', 'description' => 'required|string',
            'category' => 'nullable|in:shipping,billing,technical,account,carrier,general',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'entity_type' => 'nullable|string', 'entity_id' => 'nullable|string',
        ]);
        $ticket = $this->service->createTicket($request->user()->account, $request->user(), $data);
        return response()->json(['status' => 'success', 'data' => $ticket], 201);
    }

    public function listTickets(Request $request): JsonResponse
    {
        $data = $this->service->listTickets($request->only('account_id', 'status', 'priority', 'assigned_to', 'category'));
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function getTicket(string $ticketId): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->service->getTicket($ticketId)]);
    }

    public function replyToTicket(Request $request, string $ticketId): JsonResponse
    {
        $data = $request->validate(['body' => 'required|string', 'is_internal_note' => 'nullable|boolean']);
        $reply = $this->service->replyToTicket($ticketId, $request->user(), $data['body'], $data['is_internal_note'] ?? false);
        return response()->json(['status' => 'success', 'data' => $reply], 201);
    }

    public function assignTicket(Request $request, string $ticketId): JsonResponse
    {
        $data = $request->validate(['user_id' => 'required|uuid', 'team' => 'nullable|string']);
        return response()->json(['status' => 'success', 'data' => $this->service->assignTicket($ticketId, $data['user_id'], $data['team'] ?? null)]);
    }

    public function resolveTicket(Request $request, string $ticketId): JsonResponse
    {
        $data = $request->validate(['notes' => 'required|string']);
        return response()->json(['status' => 'success', 'data' => $this->service->resolveTicket($ticketId, $data['notes'])]);
    }

    // ═══════════════ FR-ADM-009: API Keys ════════════════════

    public function createApiKey(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string', 'scopes' => 'nullable|array']);
        $result = $this->service->createApiKey($request->user()->account, $request->user(), $data['name'], $data['scopes'] ?? []);
        return response()->json(['status' => 'success', 'data' => [
            'api_key' => $result['api_key'], 'raw_key' => $result['raw_key'],
            'warning' => 'Store this key securely. It will not be shown again.',
        ]], 201);
    }

    public function listApiKeys(Request $request): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->service->listApiKeys($request->user()->account)]);
    }

    public function revokeApiKey(string $keyId): JsonResponse
    {
        $this->service->revokeApiKey($keyId);
        return response()->json(['status' => 'success']);
    }

    public function rotateApiKey(Request $request, string $keyId): JsonResponse
    {
        $result = $this->service->rotateApiKey($keyId, $request->user());
        return response()->json(['status' => 'success', 'data' => [
            'api_key' => $result['api_key'], 'raw_key' => $result['raw_key'],
        ]], 201);
    }

    // ═══════════════ FR-ADM-010: Feature Flags ═══════════════

    public function listFeatureFlags(): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->service->listFeatureFlags()]);
    }

    public function createFeatureFlag(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => 'required|string|unique:feature_flags', 'name' => 'required|string',
            'description' => 'nullable|string', 'is_enabled' => 'nullable|boolean',
            'rollout_percentage' => 'nullable|integer|min:0|max:100',
        ]);
        return response()->json(['status' => 'success', 'data' => $this->service->createFeatureFlag($data)], 201);
    }

    public function toggleFeatureFlag(Request $request, string $flagId): JsonResponse
    {
        $data = $request->validate(['is_enabled' => 'required|boolean']);
        return response()->json(['status' => 'success', 'data' => $this->service->toggleFeatureFlag($flagId, $data['is_enabled'])]);
    }

    public function checkFeatureFlag(Request $request, string $key): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'data'    => ['key' => $key, 'enabled' => $this->service->isFeatureEnabled($key, $request->user()->account_id)],
        ]);
    }
}
