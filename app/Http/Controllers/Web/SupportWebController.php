<?php
namespace App\Http\Controllers\Web;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Http\Request;

class SupportWebController extends WebController
{
    public function index() {
        return view('pages.support.index', $this->tableData(
            'الدعم الفني', SupportTicket::class, ['الرقم','الموضوع','الأولوية','الحالة','المعيّن','التاريخ','إجراء'],
            fn($t) => [
                '<a href="'.route('support.show',$t).'" class="td-link">'.$t->ticket_number.'</a>',
                e($t->subject), $this->priBadge($t->priority), $this->statusBadge($t->status),
                e($t->assignee?->name ?? '—'), $t->created_at->format('Y-m-d'),
                '<a href="'.route('support.show',$t).'" class="btn btn-ghost">💬</a>'
            ]
        ));
    }
    public function show(SupportTicket $ticket) {
        $ticket->load('replies.user');
        return view('pages.support.show', compact('ticket'));
    }
    public function store(Request $r) {
        $d = $r->validate(['subject'=>'required','priority'=>'nullable']);
        SupportTicket::create(['ticket_number'=>'TK-'.strtoupper(uniqid()),'subject'=>$d['subject'],'priority'=>$d['priority']??'medium','status'=>'open','account_id'=>auth()->user()->account_id]);
        return back()->with('success', 'تم إنشاء التذكرة');
    }
    public function reply(SupportTicket $ticket, Request $r) {
        $r->validate(['message'=>'required']);
        SupportTicketReply::create(['support_ticket_id'=>$ticket->id,'message'=>$r->message,'user_id'=>auth()->id(),'is_customer'=>false]);
        return back()->with('success', 'تم إرسال الرد');
    }
    public function resolve(SupportTicket $ticket) {
        $ticket->update(['status'=>'resolved']);
        return redirect()->route('support.index')->with('success', 'تم حل التذكرة');
    }
    private function priBadge($p) {
        $m = ['high'=>['عالي','badge-dg'],'medium'=>['متوسط','badge-wn'],'low'=>['منخفض','badge-td']];
        $v = $m[$p] ?? ['—','badge-td'];
        return '<span class="badge '.$v[1].'">'.$v[0].'</span>';
    }
    private function tableData($title, $model, $cols, $rowFn) {
        $data = $model::latest()->paginate(20);
        $createForm = view('pages.support.partials.create-form')->render();
        return [
            'subtitle' => $data->total() . ' سجل',
            'columns' => $cols,
            'rows' => $data->map($rowFn),
            'pagination' => $data,
            'createRoute' => true,
            'createForm' => $createForm,
        ];
    }
}
