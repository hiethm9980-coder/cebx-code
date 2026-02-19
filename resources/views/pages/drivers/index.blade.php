@extends('layouts.app')
@section('title', 'السائقين')
@section('content')
<x-page-header title="السائقين" :subtitle="$subtitle ?? null">
    @if(isset($createRoute))
        <button class="btn btn-pr" data-modal-open="create-drivers">+ إنشاء</button>
    @endif
    @if(isset($exportRoute))
        <a href="{{ $exportRoute }}" class="btn btn-s">📥 تصدير</a>
    @endif
</x-page-header>

@if(isset($stats) && count($stats))
<div class="stats-grid">
    @foreach($stats as $st)
        <x-stat-card :icon="$st['icon']" :label="$st['label']" :value="$st['value']" :trend="$st['trend'] ?? null" :up="$st['up'] ?? true" />
    @endforeach
</div>
@endif

@if(isset($cards) && count($cards))
<div class="grid-3">
    @foreach($cards as $c)
        <div class="entity-card">
            <div class="top">
                <div>
                    <h3>{{ $c['title'] }}</h3>
                    @if(isset($c['subtitle'])) <p class="meta">{{ $c['subtitle'] }}</p> @endif
                </div>
                @if(isset($c['status'])) <x-badge :status="$c['status']" /> @endif
            </div>
            @if(isset($c['rows']))
                @foreach($c['rows'] as $label => $value)
                    <x-info-row :label="$label" :value="$value" />
                @endforeach
            @endif
            @if(isset($c['actions']))
                <div class="card-actions">
                    @foreach($c['actions'] as $act)
                        <a href="{{ $act['url'] }}" class="btn {{ $act['class'] ?? 'btn-s' }}">{{ $act['label'] }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</div>
@endif

@if(isset($columns) && isset($rows))
<div class="table-wrap"><table>
    <thead><tr>@foreach($columns as $col)<th>{{ $col }}</th>@endforeach</tr></thead>
    <tbody>
        @forelse($rows as $row)
            <tr>@foreach($row as $cell)<td>{!! $cell !!}</td>@endforeach</tr>
        @empty
            <tr><td colspan="{{ count($columns) }}" class="empty-state">لا توجد بيانات</td></tr>
        @endforelse
    </tbody>
</table></div>
@if(isset($pagination)) <div style="margin-top:14px">{{ $pagination->links() }}</div> @endif
@endif

@if(isset($content))
    {!! $content !!}
@endif

@if(isset($createRoute))
<x-modal id="create-drivers" title="إنشاء جديد">
    @if(isset($createForm))
        {!! $createForm !!}
    @endif
</x-modal>
@endif
@endsection
