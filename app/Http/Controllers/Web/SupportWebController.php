<?php
namespace App\Http\Controllers\Web;
use App\Models\{SupportTicket, TicketReply};
use Illuminate\Http\Request;

class SupportWebController extends WebController
{
    public function index()
    {
        $accountId = auth()->user()->account_id;
        $tickets = SupportTicket::where('account_id', $accountId)->with('user')->latest()->paginate(15);
        $openCount     = SupportTicket::where('account_id', $accountId)->where('status', 'open')->count();
        $resolvedCount = SupportTicket::where('account_id', $accountId)->where('status', 'resolved')->count();
        return view('pages.support.index', compact('tickets', 'openCount', 'resolvedCount'));
    }

    public function store(Request $request)
    {
        $v = $request->validate(['subject' => 'required|string|max:300', 'body' => 'required|string', 'category' => 'nullable|string', 'priority' => 'nullable|in:low,medium,high,urgent']);
        SupportTicket::create(array_merge($v, [
            'account_id' => auth()->user()->account_id, 'user_id' => auth()->id(),
            'reference_number' => 'TKT-' . str_pad(SupportTicket::count() + 1, 4, '0', STR_PAD_LEFT),
            'status' => 'open',
        ]));
        return back()->with('success', 'تم إنشاء التذكرة');
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['user', 'replies.user', 'shipment', 'assignee']);
        return view('pages.support.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $v = $request->validate(['body' => 'required|string']);
        TicketReply::create(['support_ticket_id' => $ticket->id, 'user_id' => auth()->id(), 'body' => $v['body'], 'is_agent' => auth()->user()->is_super_admin]);
        $ticket->update(['status' => 'in_progress']);
        return back()->with('success', 'تم إرسال الرد');
    }

    public function resolve(SupportTicket $ticket)
    {
        $ticket->update(['status' => 'resolved']);
        return back()->with('success', 'تم حل التذكرة');
    }
}
