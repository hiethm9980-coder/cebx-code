@extends('layouts.app')
@section('title', 'بوابة الأفراد | الرئيسية')

@section('content')
<div style="display:grid;gap:24px">
    <section style="padding:28px;border-radius:24px;background:linear-gradient(135deg,#0f172a,#0d9488);color:#fff">
        <div style="font-size:12px;opacity:.82;margin-bottom:8px">بوابة الأفراد / الرئيسية</div>
        <h1 style="margin:0 0 10px;font-size:30px">مرحبًا، {{ auth()->user()->name ?? 'المستخدم' }}</h1>
        <p style="margin:0;max-width:720px;line-height:1.9;color:rgba(255,255,255,.9)">
            هنا تجد كل ما يخص شحناتك الفردية: التتبع، المحفظة، والعناوين المحفوظة — كل شيء في مكان واحد.
        </p>
    </section>

    <section class="stats-grid">
        @foreach($stats as $stat)
            <x-stat-card :icon="$stat['icon']" :label="$stat['label']" :value="$stat['value']" />
        @endforeach
    </section>

    <section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px">
        @foreach([
            ['title' => 'الشحنات', 'desc' => 'تابع شحناتك الجارية والسابقة.', 'route' => 'b2c.shipments.index', 'cta' => 'فتح الشحنات'],
            ['title' => 'التتبع', 'desc' => 'ابحث برقم التتبع لأي شحنة.', 'route' => 'b2c.tracking.index', 'cta' => 'تتبع شحنة'],
            ['title' => 'المحفظة', 'desc' => 'الرصيد والمعاملات المالية.', 'route' => 'b2c.wallet.index', 'cta' => 'فتح المحفظة'],
            ['title' => 'العناوين', 'desc' => 'عناوين الشحن المحفوظة.', 'route' => 'b2c.addresses.index', 'cta' => 'إدارة العناوين'],
        ] as $card)
            <article class="card">
                <div class="card-title">{{ $card['title'] }}</div>
                <p style="margin:0 0 16px;color:var(--td);line-height:1.8">{{ $card['desc'] }}</p>
                <a href="{{ route($card['route']) }}" class="btn btn-pr">{{ $card['cta'] }}</a>
            </article>
        @endforeach
    </section>

    <section class="card">
        <div style="font-size:12px;color:var(--tm);margin-bottom:6px">روابط سريعة</div>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
            <a href="{{ route('b2c.shipments.create') }}" class="btn btn-pr">شحنة جديدة</a>
            <a href="{{ route('b2c.support.index') }}" class="btn btn-ghost">الدعم الفني</a>
            <a href="{{ route('b2c.settings.index') }}" class="btn btn-ghost">الإعدادات</a>
        </div>
    </section>
</div>
@endsection
