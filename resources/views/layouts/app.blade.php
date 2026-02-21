<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'بوابة إدارة الشحن') — Shipping Gateway</title>
    @include('components.pwa-meta')
    <meta name="pwa-sw-url" content="{{ asset('sw.js') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
<div class="app-layout">
    {{-- ═══ SIDEBAR ═══ --}}
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            @if($portalType === 'b2c')
                <div class="sidebar-logo" style="background:linear-gradient(135deg,#0D9488,#065F56)">B2C</div>
                <div class="sidebar-info">
                    <span class="sidebar-title">بوابة الشحن</span>
                    <span class="sidebar-subtitle">للأفراد</span>
                </div>
            @elseif($portalType === 'admin')
                <div class="sidebar-logo" style="background:linear-gradient(135deg,#7C3AED,#4C1D95)">SYS</div>
                <div class="sidebar-info">
                    <span class="sidebar-title">Shipping Gateway</span>
                    <span class="sidebar-subtitle">لوحة مدير النظام</span>
                </div>
            @else
                <div class="sidebar-logo">B2B</div>
                <div class="sidebar-info">
                    <span class="sidebar-title">Shipping Gateway</span>
                    <span class="sidebar-subtitle">بوابة الأعمال</span>
                </div>
            @endif
        </div>

        <nav class="sidebar-nav">
            @php
                $currentRoute = Route::currentRouteName() ?? '';
                $acctId = auth()->user()->account_id;
                $isAdminPortal = ($portalType ?? 'b2b') === 'admin';

                if ($isAdminPortal) {
                    $unreadNotifs = \App\Models\Notification::whereNull('read_at')->count();
                    $openTickets = \App\Models\SupportTicket::where('status', 'open')->count();
                    $processingShipments = \App\Models\Shipment::whereIn('status', ['payment_pending','purchased','picked_up','in_transit','out_for_delivery'])->count();
                } else {
                    $unreadNotifs = \App\Models\Notification::where('account_id', $acctId)->whereNull('read_at')->count();
                    $openTickets = \App\Models\SupportTicket::where('account_id', $acctId)->where('status', 'open')->count();
                    $processingShipments = \App\Models\Shipment::where('account_id', $acctId)->whereIn('status', ['payment_pending','purchased','picked_up','in_transit','out_for_delivery'])->count();
                }

                $b2cMenu = [
                    ['id' => 'dashboard', 'route' => 'dashboard', 'icon' => '🏠', 'label' => 'الرئيسية'],
                    ['id' => 'shipments', 'route' => 'shipments.index', 'icon' => '📦', 'label' => 'شحناتي', 'badge' => $processingShipments],
                    ['id' => 'tracking', 'route' => 'tracking.index', 'icon' => '🔍', 'label' => 'التتبع'],
                    ['id' => 'wallet', 'route' => 'wallet.index', 'icon' => '💰', 'label' => 'المحفظة'],
                    ['d' => true],
                    ['id' => 'addresses', 'route' => 'addresses.index', 'icon' => '📒', 'label' => 'العناوين'],
                    ['id' => 'support', 'route' => 'support.index', 'icon' => '🎧', 'label' => 'الدعم', 'badge' => $openTickets],
                    ['id' => 'settings', 'route' => 'settings.index', 'icon' => '⚙️', 'label' => 'الإعدادات'],
                ];

                $b2bMenu = [
                    ['id' => 'dashboard', 'route' => 'dashboard', 'icon' => '📊', 'label' => 'لوحة التحكم'],
                    ['id' => 'shipments', 'route' => 'shipments.index', 'icon' => '📦', 'label' => 'الشحنات', 'badge' => $processingShipments],
                    ['id' => 'orders', 'route' => 'orders.index', 'icon' => '🛒', 'label' => 'الطلبات'],
                    ['id' => 'stores', 'route' => 'stores.index', 'icon' => '🏪', 'label' => 'المتاجر'],
                    ['id' => 'wallet', 'route' => 'wallet.index', 'icon' => '💰', 'label' => 'المحفظة'],
                    ['id' => 'reports', 'route' => 'reports.index', 'icon' => '📊', 'label' => 'التقارير'],
                    ['d' => true],
                    ['id' => 'users', 'route' => 'users.index', 'icon' => '👥', 'label' => 'المستخدمون'],
                    ['id' => 'roles', 'route' => 'roles.index', 'icon' => '🔐', 'label' => 'الأدوار'],
                    ['id' => 'invitations', 'route' => 'invitations.index', 'icon' => '📨', 'label' => 'الدعوات'],
                    ['d' => true],
                    ['id' => 'settings', 'route' => 'settings.index', 'icon' => '⚙️', 'label' => 'الإعدادات'],
                ];

                $adminMenu = [
                    ['g' => 'العمليات'],
                    ['id' => 'dashboard', 'route' => 'dashboard', 'icon' => '📊', 'label' => 'لوحة التحكم'],
                    ['id' => 'shipments', 'route' => 'shipments.index', 'icon' => '📦', 'label' => 'الشحنات', 'badge' => $processingShipments],
                    ['id' => 'orders', 'route' => 'orders.index', 'icon' => '🛒', 'label' => 'الطلبات'],
                    ['id' => 'tracking', 'route' => 'tracking.index', 'icon' => '🔍', 'label' => 'التتبع'],
                    ['id' => 'stores', 'route' => 'stores.index', 'icon' => '🏪', 'label' => 'المتاجر'],

                    ['g' => 'المالية'],
                    ['id' => 'wallet', 'route' => 'wallet.index', 'icon' => '💰', 'label' => 'المحفظة'],
                    ['id' => 'financial', 'route' => 'financial.index', 'icon' => '💳', 'label' => 'المالية'],
                    ['id' => 'pricing', 'route' => 'pricing.index', 'icon' => '🏷️', 'label' => 'التسعير'],

                    ['g' => 'اللوجستيات'],
                    ['id' => 'containers', 'route' => 'containers.index', 'icon' => '🚢', 'label' => 'الحاويات'],
                    ['id' => 'customs', 'route' => 'customs.index', 'icon' => '🛃', 'label' => 'الجمارك'],
                    ['id' => 'vessels', 'route' => 'vessels.index', 'icon' => '⛴️', 'label' => 'السفن'],
                    ['id' => 'schedules', 'route' => 'schedules.index', 'icon' => '📅', 'label' => 'الجداول'],
                    ['id' => 'drivers', 'route' => 'drivers.index', 'icon' => '🚛', 'label' => 'السائقين'],
                    ['id' => 'hscodes', 'route' => 'hscodes.index', 'icon' => '🔢', 'label' => 'أكواد HS'],

                    ['g' => 'الامتثال'],
                    ['id' => 'kyc', 'route' => 'kyc.index', 'icon' => '🪪', 'label' => 'KYC'],
                    ['id' => 'dg', 'route' => 'dg.index', 'icon' => '☣️', 'label' => 'البضائع الخطرة'],
                    ['id' => 'risk', 'route' => 'risk.index', 'icon' => '⚠️', 'label' => 'المخاطر'],
                    ['id' => 'claims', 'route' => 'claims.index', 'icon' => '📋', 'label' => 'المطالبات'],

                    ['g' => 'الإدارة'],
                    ['id' => 'organizations', 'route' => 'organizations.index', 'icon' => '🏢', 'label' => 'المنظمات'],
                    ['id' => 'companies', 'route' => 'companies.index', 'icon' => '🏭', 'label' => 'الشركات'],
                    ['id' => 'branches', 'route' => 'branches.index', 'icon' => '🏬', 'label' => 'الفروع'],
                    ['id' => 'users', 'route' => 'users.index', 'icon' => '👥', 'label' => 'المستخدمون'],
                    ['id' => 'roles', 'route' => 'roles.index', 'icon' => '🔐', 'label' => 'الأدوار'],
                    ['id' => 'invitations', 'route' => 'invitations.index', 'icon' => '📨', 'label' => 'الدعوات'],

                    ['g' => 'النظام'],
                    ['id' => 'admin', 'route' => 'admin.index', 'icon' => '🛡️', 'label' => 'الإدارة العامة'],
                    ['id' => 'audit', 'route' => 'audit.index', 'icon' => '📜', 'label' => 'التدقيق'],
                    ['id' => 'reports', 'route' => 'reports.index', 'icon' => '📈', 'label' => 'التقارير'],
                    ['id' => 'notifications', 'route' => 'notifications.index', 'icon' => '🔔', 'label' => 'الإشعارات', 'badge' => $unreadNotifs],
                    ['id' => 'support', 'route' => 'support.index', 'icon' => '🎧', 'label' => 'الدعم', 'badge' => $openTickets],
                    ['id' => 'addresses', 'route' => 'addresses.index', 'icon' => '📒', 'label' => 'العناوين'],
                    ['id' => 'settings', 'route' => 'settings.index', 'icon' => '⚙️', 'label' => 'الإعدادات'],
                ];

                $menu = match($portalType) {
                    'b2c' => $b2cMenu,
                    'admin' => $adminMenu,
                    default => $b2bMenu,
                };
            @endphp

            @foreach($menu as $item)
                @if(isset($item['d']))
                    <div class="sidebar-divider"></div>
                @elseif(isset($item['g']))
                    <div class="sidebar-group-label">{{ $item['g'] }}</div>
                @else
                    @php
                        $isActive = str_starts_with($currentRoute, $item['id']) || $currentRoute === $item['route'];
                    @endphp
                    <a href="{{ route($item['route']) }}"
                       class="sidebar-item {{ $isActive ? 'active' : '' }}"
                       @if($portalType === 'b2c' && $isActive) style="background:rgba(13,148,136,0.13);color:#0D9488"
                       @elseif($portalType === 'admin' && $isActive) style="background:rgba(124,58,237,0.13);color:#7C3AED"
                       @endif>
                        <span class="icon">{{ $item['icon'] }}</span>
                        <span>{{ $item['label'] }}</span>
                        @if(isset($item['badge']) && $item['badge'] > 0)
                            <span class="badge-count">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">🚪 تسجيل الخروج</button>
            </form>
        </div>
    </aside>

    {{-- ═══ MAIN AREA ═══ --}}
    <div class="main-area">
        <header class="topbar">
            <div style="display:flex;align-items:center;gap:12px">
                @if($portalType === 'admin')
                    <span style="font-size:14px">🛡️</span>
                    <span style="font-weight:600;color:var(--tx);font-size:14px">مدير النظام</span>
                @elseif($portalType === 'b2b')
                    <span style="font-size:14px">🏢</span>
                    <span style="font-weight:600;color:var(--tx);font-size:14px">{{ auth()->user()->account->name ?? 'شركة التقنية المتقدمة' }}</span>
                @else
                    <span style="font-weight:600;color:var(--tx);font-size:15px">@yield('page-title', '')</span>
                @endif
            </div>
            <div class="topbar-user">
                <button class="topbar-bell" onclick="window.location='{{ route('notifications.index') }}'">
                    🔔
                    @if($unreadNotifs > 0)<span class="dot"></span>@endif
                </button>
                <div style="display:flex;align-items:center;gap:10px">
                    @php
                        $avatarStyle = match($portalType) {
                            'b2c' => 'background:linear-gradient(135deg,#0D9488,#065F56);color:#fff',
                            'admin' => 'background:linear-gradient(135deg,#7C3AED,#4C1D95);color:#fff',
                            default => '',
                        };
                    @endphp
                    <div class="topbar-avatar" style="{{ $avatarStyle }}">
                        {{ mb_substr(auth()->user()->name ?? 'م', 0, 1) }}
                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--tx)">{{ auth()->user()->name ?? 'المستخدم' }}</div>
                        @if($portalType !== 'b2c')
                            <div style="font-size:11px;color:var(--td)">{{ $portalType === 'admin' ? 'مدير النظام' : (auth()->user()->role_name ?? 'مدير') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </header>

        <main class="content">
            {{-- Toast Notifications --}}
            @if(session('success'))
                <div class="toast-container"><div class="toast toast-success">✅ {{ session('success') }}</div></div>
            @endif
            @if(session('error'))
                <div class="toast-container"><div class="toast toast-danger">❌ {{ session('error') }}</div></div>
            @endif
            @if(session('warning'))
                <div class="toast-container"><div class="toast toast-warning">⚠️ {{ session('warning') }}</div></div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
    window.PWA = window.PWA || {};
    window.PWA.swUrl = document.querySelector('meta[name="pwa-sw-url"]')?.getAttribute('content') || '{{ asset("sw.js") }}';
    window.PWA.scope = '{{ rtrim(url("/"), "/") }}/';
</script>
<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('js/pwa.js') }}" defer></script>
@stack('scripts')
</body>
</html>
