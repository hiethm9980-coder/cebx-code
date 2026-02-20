@extends('layouts.app')
@section('title', 'المحفظة')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">💰 {{ $portalType === 'b2c' ? 'محفظتي' : 'المحفظة' }}</h1>
    <button class="btn btn-pr" data-modal-open="topup-wallet"
            @if($portalType === 'b2c') style="background:#0D9488" @endif>+ شحن الرصيد</button>
</div>

{{-- ═══ BALANCE CARD ═══ --}}
<div style="background:linear-gradient(135deg,{{ $portalType === 'b2c' ? '#0D9488,#065F56,#134E4A' : '#3B82F6,#1D4ED8,#7C3AED' }});border-radius:20px;padding:36px 32px;margin-bottom:28px;position:relative;overflow:hidden">
    <div style="position:absolute;top:-30px;left:-30px;width:140px;height:140px;background:rgba(255,255,255,0.05);border-radius:50%"></div>
    <div style="position:absolute;bottom:-40px;right:40px;width:100px;height:100px;background:rgba(255,255,255,0.03);border-radius:50%"></div>
    <div style="position:relative">
        <div style="font-size:14px;color:rgba(255,255,255,0.73)">الرصيد المتاح</div>
        <div style="font-size:48px;font-weight:800;color:#fff;font-family:monospace;margin:8px 0">
            {{ number_format($wallet->available_balance ?? 0, 2) }} <span style="font-size:20px">ر.س</span>
        </div>
        @if($portalType === 'b2b')
            <div style="font-size:13px;color:rgba(255,255,255,0.66)">آخر عملية: {{ $lastTransaction?->description ?? '—' }}</div>
        @endif
    </div>
</div>

@if($portalType === 'b2b')
    <div class="stats-grid" style="margin-bottom:24px">
        <x-stat-card icon="💸" label="مصروفات الشهر" :value="number_format($monthlyExpenses ?? 0)" />
        <x-stat-card icon="💳" label="إيداعات الشهر" :value="number_format($monthlyDeposits ?? 0)" />
        <x-stat-card icon="🔄" label="عدد المعاملات" :value="$transactionCount ?? 0" />
    </div>
@endif

{{-- ═══ TRANSACTIONS ═══ --}}
<x-card title="📋 {{ $portalType === 'b2c' ? 'آخر المعاملات' : 'سجل المعاملات' }}">
    @if($portalType === 'b2b')
        <div class="table-wrap">
            <table>
                <thead><tr><th>النوع</th><th>الوصف</th><th>المبلغ</th><th>الرصيد بعد</th><th>التاريخ</th></tr></thead>
                <tbody>
                    @forelse($transactions ?? [] as $tx)
                        @php $isCredit = in_array($tx->type, ['topup', 'refund']); @endphp
                        <tr>
                            <td><span class="badge {{ $isCredit ? 'badge-ac' : 'badge-dg' }}">{{ $isCredit ? 'إيداع' : 'خصم' }}</span></td>
                            <td>{{ $tx->description }}</td>
                            <td style="color:{{ $isCredit ? 'var(--ac)' : 'var(--dg)' }};font-family:monospace;font-weight:600">{{ $isCredit ? '+' : '-' }}{{ number_format($tx->amount, 2) }}</td>
                            <td style="font-family:monospace">{{ number_format($tx->running_balance ?? 0, 2) }}</td>
                            <td>{{ $tx->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">لا توجد معاملات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:0">
            @forelse($transactions ?? [] as $tx)
                @php $isCredit = in_array($tx->type, ['topup', 'refund']); @endphp
                <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 0;border-bottom:1px solid var(--bd)">
                    <div style="display:flex;gap:14px;align-items:center">
                        <div style="width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;background:{{ $isCredit ? 'rgba(16,185,129,0.13)' : 'rgba(239,68,68,0.13)' }}">
                            {{ $isCredit ? '↑' : '↓' }}
                        </div>
                        <div>
                            <div style="font-size:14px;color:var(--tx)">{{ $tx->description }}</div>
                            <div style="font-size:12px;color:var(--td);margin-top:2px">{{ $tx->created_at->format('d/m') }}</div>
                        </div>
                    </div>
                    <span style="font-family:monospace;font-weight:700;font-size:16px;color:{{ $isCredit ? '#10B981' : '#EF4444' }}">
                        {{ $isCredit ? '+' : '-' }}{{ number_format($tx->amount, 2) }}
                    </span>
                </div>
            @empty
                <div class="empty-state">لا توجد معاملات</div>
            @endforelse
        </div>
    @endif
    @if(isset($transactions) && $transactions instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div style="margin-top:14px">{{ $transactions->links() }}</div>
    @endif
</x-card>

{{-- ═══ TOPUP MODAL ═══ --}}
<x-modal id="topup-wallet" title="شحن الرصيد">
    <form method="POST" action="{{ route('wallet.topup') }}">
        @csrf
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px">
            @foreach([100, 250, 500, 1000] as $amount)
                <button type="button" class="amount-btn"
                        style="padding:14px;background:var(--sf);border:1px solid var(--bd);border-radius:8px;color:var(--tx);font-weight:600;font-size:16px;cursor:pointer;font-family:monospace"
                        onclick="document.getElementById('topupAmount').value={{ $amount }}">
                    {{ $amount }}
                </button>
            @endforeach
        </div>
        <div style="margin-bottom:16px">
            <label class="form-label">مبلغ مخصص</label>
            <input type="number" name="amount" id="topupAmount" placeholder="0.00 ر.س" step="0.01" class="form-input" value="500">
        </div>
        <div style="margin-bottom:16px">
            <label class="form-label">وسيلة الدفع</label>
            <select name="payment_method" class="form-input">
                @if($portalType === 'b2b')
                    <option>تحويل بنكي</option>
                @endif
                <option>مدى</option>
                <option>فيزا/ماستركارد</option>
                <option>Apple Pay</option>
                <option>STC Pay</option>
            </select>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
            <button type="button" class="btn btn-s" data-modal-close>إلغاء</button>
            <button type="submit" class="btn btn-pr" @if($portalType === 'b2c') style="background:#0D9488" @endif>شحن الرصيد</button>
        </div>
    </form>
</x-modal>
@endsection
