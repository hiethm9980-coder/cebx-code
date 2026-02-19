<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * CBEX GROUP — Support Ticket Controller
 */
class SupportTicketController extends Controller
{
    public function __construct(protected AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = SupportTicket::where('account_id', $request->user()->account_id)
            ->with(['assignee:id,name', 'creator:id,name']);

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('priority')) $query->where('priority', $request->priority);
        if ($request->filled('category')) $query->where('category', $request->category);
        if ($request->filled('shipment_id')) $query->where('shipment_id', $request->shipment_id);
        if ($request->filled('assigned_to')) $query->where('assigned_to', $request->assigned_to);
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('subject', 'ilike', "%{$request->search}%")
                  ->orWhere('ticket_number', 'ilike', "%{$request->search}%");
            });
        }

        $sort = $request->get('sort', '-created_at');
        $dir = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $col = ltrim($sort, '-');
        $query->orderBy($col, $dir);

        return response()->json(['data' => $query->withCount('replies')->paginate(20)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => 'required|string|max:300',
            'description' => 'required|string|max:5000',
            'category' => 'required|in:shipment,billing,technical,customs,general,complaint',
            'priority' => 'in:low,medium,high,urgent',
            'shipment_id' => 'nullable|uuid|exists:shipments,id',
        ]);

        $data['id'] = Str::uuid()->toString();
        $data['account_id'] = $request->user()->account_id;
        $data['created_by'] = $request->user()->id;
        $data['ticket_number'] = 'TKT-' . strtoupper(Str::random(8));
        $data['status'] = 'open';
        $data['priority'] = $data['priority'] ?? 'medium';

        // Calculate SLA deadline
        $slaHours = match ($data['priority']) {
            'urgent' => 4, 'high' => 8, 'medium' => 24, default => 48,
        };
        $data['sla_deadline'] = now()->addHours($slaHours);

        $ticket = SupportTicket::create($data);
        $this->audit->log('ticket.created', $ticket);

        return response()->json([
            'data' => $ticket->load(['creator:id,name']),
            'message' => "تم إنشاء التذكرة #{$ticket->ticket_number}",
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $ticket = SupportTicket::where('account_id', $request->user()->account_id)
            ->with(['replies.author:id,name', 'creator:id,name', 'assignee:id,name', 'shipment:id,tracking_number,status'])
            ->findOrFail($id);

        return response()->json(['data' => $ticket]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $ticket = SupportTicket::where('account_id', $request->user()->account_id)->findOrFail($id);

        $data = $request->validate([
            'status' => 'in:open,in_progress,waiting_customer,waiting_internal,resolved,closed',
            'priority' => 'in:low,medium,high,urgent',
            'assigned_to' => 'nullable|uuid|exists:users,id',
            'category' => 'in:shipment,billing,technical,customs,general,complaint',
            'resolution_notes' => 'nullable|string|max:5000',
        ]);

        if (isset($data['status']) && in_array($data['status'], ['resolved', 'closed'])) {
            $data['resolved_at'] = now();
        }

        $ticket->update($data);
        $this->audit->log('ticket.updated', $ticket);

        return response()->json(['data' => $ticket->fresh()]);
    }

    /**
     * Add reply to a ticket
     */
    public function reply(Request $request, string $id): JsonResponse
    {
        $ticket = SupportTicket::where('account_id', $request->user()->account_id)->findOrFail($id);

        $data = $request->validate([
            'message' => 'required|string|max:5000',
            'is_internal' => 'boolean',
        ]);

        $reply = SupportTicketReply::create([
            'id' => Str::uuid()->toString(),
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $data['message'],
            'is_internal' => $data['is_internal'] ?? false,
        ]);

        // Auto-update status
        $isAgent = $request->user()->hasRole(['admin', 'agent', 'support']);
        if ($isAgent && $ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        } elseif (!$isAgent && $ticket->status === 'waiting_customer') {
            $ticket->update(['status' => 'in_progress']);
        }

        return response()->json([
            'data' => $reply->load('author:id,name'),
            'message' => 'تم إضافة الرد',
        ], 201);
    }

    /**
     * Assign ticket to agent
     */
    public function assign(Request $request, string $id): JsonResponse
    {
        $ticket = SupportTicket::where('account_id', $request->user()->account_id)->findOrFail($id);

        $data = $request->validate(['assigned_to' => 'required|uuid|exists:users,id']);
        $ticket->update(['assigned_to' => $data['assigned_to'], 'status' => 'in_progress']);

        return response()->json(['data' => $ticket->fresh()->load('assignee:id,name')]);
    }

    /**
     * Escalate ticket
     */
    public function escalate(Request $request, string $id): JsonResponse
    {
        $ticket = SupportTicket::where('account_id', $request->user()->account_id)->findOrFail($id);
        $ticket->update([
            'priority' => 'urgent',
            'escalated' => true,
            'escalated_at' => now(),
        ]);

        $this->audit->log('ticket.escalated', $ticket);
        return response()->json(['data' => $ticket->fresh(), 'message' => 'تم تصعيد التذكرة']);
    }

    /**
     * Get ticket stats/dashboard
     */
    public function stats(Request $request): JsonResponse
    {
        $accountId = $request->user()->account_id;
        $base = SupportTicket::where('account_id', $accountId);

        return response()->json(['data' => [
            'total' => (clone $base)->count(),
            'open' => (clone $base)->where('status', 'open')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'waiting' => (clone $base)->whereIn('status', ['waiting_customer', 'waiting_internal'])->count(),
            'resolved' => (clone $base)->whereIn('status', ['resolved', 'closed'])->count(),
            'overdue' => (clone $base)->whereNotIn('status', ['resolved', 'closed'])
                ->where('sla_deadline', '<', now())->count(),
            'by_priority' => [
                'urgent' => (clone $base)->where('priority', 'urgent')->whereNotIn('status', ['resolved', 'closed'])->count(),
                'high' => (clone $base)->where('priority', 'high')->whereNotIn('status', ['resolved', 'closed'])->count(),
                'medium' => (clone $base)->where('priority', 'medium')->whereNotIn('status', ['resolved', 'closed'])->count(),
                'low' => (clone $base)->where('priority', 'low')->whereNotIn('status', ['resolved', 'closed'])->count(),
            ],
            'by_category' => (clone $base)->selectRaw('category, count(*) as count')
                ->groupBy('category')->pluck('count', 'category'),
            'avg_resolution_hours' => round((clone $base)->whereNotNull('resolved_at')
                ->selectRaw('AVG(EXTRACT(EPOCH FROM (resolved_at - created_at))/3600) as avg')
                ->value('avg') ?? 0, 1),
        ]]);
    }

    public function close(Request $request, string $id): JsonResponse
    {
        $ticket = \App\Models\SupportTicket::where('account_id', $request->user()->account_id)->findOrFail($id);
        $ticket->update(['status' => 'closed', 'resolved_at' => now()]);
        $this->audit->log('ticket.closed', $ticket, $request);
        return response()->json(['data' => $ticket]);
    }
}
