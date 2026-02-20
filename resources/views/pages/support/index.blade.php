@extends('layouts.app')
@section('title', 'الدعم والمساعدة')

@section('content')
<h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0 0 24px">🎧 الدعم والمساعدة</h1>

{{-- ═══ FAQ ═══ --}}
<x-card title="❓ الأسئلة الشائعة">
    @foreach([
        ['كيف أتتبع شحنتي؟', 'يمكنك تتبع شحنتك من خلال صفحة التتبع بإدخال رقم التتبع الخاص بك، أو من خلال قائمة شحناتي.'],
        ['كم يستغرق التوصيل؟', 'يعتمد وقت التوصيل على الخدمة المختارة والوجهة. عادة 1-3 أيام للشحن المحلي و5-10 أيام للدولي.'],
        ['كيف أسترجع شحنة؟', 'اذهب لتفاصيل الشحنة واختر "طلب إرجاع". سيتم ترتيب استلام الشحنة من المستلم.'],
        ['كيف أشحن رصيد المحفظة؟', 'من صفحة المحفظة، اضغط "شحن الرصيد" واختر المبلغ ووسيلة الدفع المناسبة.'],
    ] as $i => $faq)
        <div style="border-bottom:1px solid var(--bd)">
            <button class="faq-toggle" onclick="toggleFaq({{ $i }})" style="display:flex;justify-content:space-between;align-items:center;padding:16px 0;cursor:pointer;width:100%;background:none;border:none;text-align:right;font-family:inherit">
                <span style="font-weight:600;color:var(--tx);font-size:14px">{{ $faq[0] }}</span>
                <span style="color:var(--td);transition:transform 0.2s" id="faqIcon{{ $i }}">▼</span>
            </button>
            <p id="faqAnswer{{ $i }}" style="color:var(--tm);font-size:13px;margin:0 0 16px;line-height:1.8;display:none">{{ $faq[1] }}</p>
        </div>
    @endforeach
</x-card>

{{-- ═══ TICKETS ═══ --}}
<x-card title="🎫 تذاكري">
    <x-slot:action>
        @php $ticketBtnStyle = $portalType === 'b2c' ? 'background:#0D9488' : ''; @endphp
        <button class="btn btn-pr btn-sm" data-modal-open="new-ticket" style="{{ $ticketBtnStyle }}">+ تذكرة جديدة</button>
    </x-slot:action>
    @forelse($tickets ?? [] as $ticket)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 0;border-bottom:1px solid var(--bd)">
            <div>
                <span style="font-family:monospace;color:#0D9488;font-weight:600">{{ $ticket->reference_number ?? '#TKT-' . str_pad($ticket->id, 3, '0', STR_PAD_LEFT) }}</span>
                <div style="font-size:13px;color:var(--tx);margin-top:4px">{{ $ticket->subject }}</div>
            </div>
            <div style="text-align:left">
                <x-badge :status="$ticket->status" />
                <div style="font-size:11px;color:var(--td);margin-top:4px">{{ $ticket->created_at->format('d/m') }}</div>
            </div>
        </div>
    @empty
        <div class="empty-state">لا توجد تذاكر</div>
    @endforelse
</x-card>

{{-- ═══ CONTACT METHODS ═══ --}}
<div class="grid-3">
    @foreach([
        ['icon' => '📧', 'title' => 'البريد الإلكتروني', 'info' => 'support@ship.sa', 'desc' => 'الرد خلال 24 ساعة'],
        ['icon' => '📞', 'title' => 'الهاتف', 'info' => '920000XXX', 'desc' => 'أحد - خميس، 9ص - 6م'],
        ['icon' => '💬', 'title' => 'المحادثة المباشرة', 'info' => 'متاح الآن', 'desc' => 'متوسط الانتظار: 2 دقيقة'],
    ] as $contact)
        <x-card>
            <div style="text-align:center">
                <div style="font-size:36px;margin-bottom:12px">{{ $contact['icon'] }}</div>
                <div style="font-weight:600;color:var(--tx);font-size:15px;margin-bottom:4px">{{ $contact['title'] }}</div>
                <div style="color:#0D9488;font-size:14px;font-weight:600;margin-bottom:4px">{{ $contact['info'] }}</div>
                <div style="color:var(--td);font-size:12px">{{ $contact['desc'] }}</div>
            </div>
        </x-card>
    @endforeach
</div>

{{-- ═══ NEW TICKET MODAL ═══ --}}
<x-modal id="new-ticket" title="تذكرة دعم جديدة">
    <form method="POST" action="{{ route('support.store') }}">
        @csrf
        <div style="margin-bottom:16px">
            <label class="form-label">نوع المشكلة</label>
            <select name="category" class="form-input">
                <option>مشكلة في شحنة</option><option>استفسار عام</option><option>مشكلة تقنية</option><option>اقتراح</option>
            </select>
        </div>
        <div style="margin-bottom:16px"><label class="form-label">رقم الشحنة (اختياري)</label><input type="text" name="shipment_ref" placeholder="TRK-XXXX" class="form-input"></div>
        <div style="margin-bottom:16px"><label class="form-label">الموضوع</label><input type="text" name="subject" placeholder="عنوان المشكلة" class="form-input" required></div>
        <div style="margin-bottom:16px">
            <label class="form-label">التفاصيل</label>
            <textarea name="message" rows="4" placeholder="اشرح المشكلة بالتفصيل..." class="form-input" style="resize:vertical" required></textarea>
        </div>
        <button type="submit" class="btn btn-pr" style="width:100%;{{ $portalType === 'b2c' ? 'background:#0D9488' : '' }}">إرسال التذكرة</button>
    </form>
</x-modal>

@push('scripts')
<script>
function toggleFaq(i) {
    const a = document.getElementById('faqAnswer' + i);
    const icon = document.getElementById('faqIcon' + i);
    if (a.style.display === 'none') { a.style.display = 'block'; icon.style.transform = 'rotate(180deg)'; }
    else { a.style.display = 'none'; icon.style.transform = 'none'; }
}
</script>
@endpush
@endsection
