{{-- ═══ PWA Meta Tags — Include in <head> ═══ --}}

{{-- Web App Manifest --}}
<link rel="manifest" href="{{ asset('manifest.json') }}">

{{-- Theme Color --}}
<meta name="theme-color" content="#3B82F6">

{{-- iOS Support --}}
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="ShipGateway">
<link rel="apple-touch-icon" href="{{ asset('icons/icon-152x152.png') }}">
<link rel="apple-touch-icon" sizes="192x192" href="{{ asset('icons/icon-192x192.png') }}">
<link rel="apple-touch-icon" sizes="512x512" href="{{ asset('icons/icon-512x512.png') }}">

{{-- iOS Splash Screens (optional — uncomment if needed)
<link rel="apple-touch-startup-image" href="{{ asset('icons/splash-1125x2436.png') }}"
      media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)">
--}}

{{-- Windows Tiles --}}
<meta name="msapplication-TileColor" content="#0B0F1A">
<meta name="msapplication-TileImage" content="{{ asset('icons/icon-144x144.png') }}">

{{-- Favicon --}}
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/icon-96x96.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('icons/icon-72x72.png') }}">

{{-- PWA Description --}}
<meta name="description" content="بوابة إدارة الشحن واللوجستيات — Shipping Gateway">
<meta name="mobile-web-app-capable" content="yes">
