<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php $b = \App\Models\Setting::branding(); @endphp

    <title>{{ $b['meta_title'] }}</title>
    <meta name="description" content="{{ $b['meta_description'] }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $b['app_name'] }}">
    <meta property="og:title" content="{{ $b['meta_title'] }}">
    <meta property="og:description" content="{{ $b['meta_description'] }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ url($b['og_image']) }}">
    <meta property="og:locale" content="fr_FR">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $b['meta_title'] }}">
    <meta name="twitter:description" content="{{ $b['meta_description'] }}">
    <meta name="twitter:image" content="{{ url($b['og_image']) }}">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#004B5E">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $b['app_name'] }}">
    <meta name="application-name" content="{{ $b['app_name'] }}">
    <meta name="msapplication-TileColor" content="#004B5E">
    <meta name="msapplication-tap-highlight" content="no">
    <meta name="format-detection" content="telephone=no">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ $b['favicon_url'] }}">
    <link rel="shortcut icon" type="image/png" href="{{ $b['favicon_url'] }}">
    <link rel="apple-touch-icon" href="{{ $b['favicon_url'] }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ $b['favicon_url'] }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $b['favicon_url'] }}">
    <link rel="apple-touch-icon" sizes="167x167" href="{{ $b['favicon_url'] }}">

    <!-- Splash Screen for iOS -->
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-startup-image" href="/images/logo.png?v=2">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Branding injecté pour le SPA (évite un flash au chargement) -->
    <script>window.__BRANDING__ = @json($b);</script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div id="app"></div>
</body>
</html>