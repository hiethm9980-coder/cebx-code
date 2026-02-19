@extends('layouts.app')
@section('title', 'المحفظة')
@section('content')
<x-page-header title="المحفظة">
    <button class="btn btn-pr" data-modal-open="topup-wallet">+ شحن</button>
    <button class="btn btn-wn" data-modal-open="hold-wallet">🔒 حجز مبلغ</button>
</x-page-header>

<div class="stats-grid">
    <x-stat-card icon="💰" label="الرصيد المتاح" :value="number_format((float)($wallet->available_balance ?? 0), 2) . ' ر.س'" />
    <x-stat-card icon="🔒" label="محجوز" :value="number_format((float)($wallet->locked_balance ?? 0), 2) . ' ر.س'" />
    <x-stat-card icon="📊" label="إجمالي المصروفات" :value="number_format(0, 2) . ' ر.س'" />
</div>

<x-card title="كشف حساب">
    <div class="table-wrap">
        <table>
            <thead><tr><th>التاريخ</th><th>النوع</th><th>الوصف</th><th>المبلغ</th><th>الرصيد</th></tr></thead>
            <tbody>
                @foreach($transactions as $tx)
                    @php
                        $types = ['topup' => ['إيداع', 'badge-ac'], 'charge' => ['خصم', 'badge-dg'], 'refund' => ['استرداد', 'badge-in'], 'hold' => ['حجز', 'badge-wn']];
                        $t = $types[$tx->type] ?? ['—', 'badge-td'];
                        $isCredit = in_array($tx->type, ['topup', 'refund']);
                    @endphp
                    <tr>
                        <td>{{ $tx->created_at->format('Y-m-d H:i') }}</td>
                        <td><span class="badge {{ $t[1] }}">{{ $t[0] }}</span></td>
                        <td>{{ $tx->description }}</td>
                        <td style="color:{{ $isCredit ? 'var(--ac)' : 'var(--dg)' }};font-weight:600">{{ $isCredit ? '+' : '-' }}{{ number_format($tx->amount, 2) }}</td>
                        <td style="font-family:monospace">{{ number_format((float)($tx->running_balance ?? 0), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-card>

<x-card title="وسائل الدفع">
    <div class="grid-2">
        @foreach($paymentMethods as $pm)
            <div style="background:var(--sf);border:1px solid {{ $pm->is_default ? 'rgba(59,130,246,0.4)' : 'var(--bd)' }};border-radius:14px;padding:16px">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                    <span class="badge badge-in">{{ $pm->type }}</span>
                    @if($pm->is_default) <span class="badge badge-ac">افتراضي</span> @endif
                </div>
                <p style="font-family:monospace;font-size:16px;margin:0 0 4px">•••• {{ $pm->last_four }}</p>
                <p style="color:var(--td);font-size:11px;margin:0">تنتهي: {{ $pm->expiry_date }}</p>
            </div>
        @endforeach
    </div>
</x-card>

<x-modal id="topup-wallet" title="شحن المحفظة">
    <form method="POST" action="{{ route('wallet.topup') }}">@csrf
        <div class="form-group"><label class="form-label">المبلغ (ر.س) *</label><input name="amount" type="number" step="0.01" class="form-control" required></div>
        <div class="form-group"><label class="form-label">الوسيلة</label>
            <select name="payment_method_id" class="form-control">
                @foreach($paymentMethods as $pm)
                    <option value="{{ $pm->id }}">{{ $pm->type }} •••• {{ $pm->last_four }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-pr" style="margin-top:10px">شحن</button>
    </form>
</x-modal>

<x-modal id="hold-wallet" title="حجز مبلغ">
    <form method="POST" action="{{ route('wallet.hold') }}">@csrf
        <div class="form-group"><label class="form-label">المبلغ *</label><input name="amount" type="number" step="0.01" class="form-control" required></div>
        <div class="form-group"><label class="form-label">السبب</label><input name="reason" class="form-control"></div>
        <button type="submit" class="btn btn-wn" style="margin-top:10px">حجز</button>
    </form>
</x-modal>
@endsection
