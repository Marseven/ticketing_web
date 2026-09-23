<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $b = \App\Models\Setting::branding();
        // Métadonnées propres à la page, résolues côté serveur : les aperçus
        // de lien (WhatsApp, Facebook) et les moteurs n'exécutent pas le JS.
        $m = $meta ?? app(\App\Services\PageMeta::class)->forPath(request()->path());
    @endphp

    <title>{{ $m['title'] }}</title>
    <meta name="description" content="{{ $m['description'] }}">
    <link rel="canonical" href="{{ $m['url'] }}">
    @if (!empty($m['robots']))
        <meta name="robots" content="{{ $m['robots'] }}">
    @endif

    <!-- Open Graph -->
    <meta property="og:type" content="{{ $m['type'] }}">
    <meta property="og:site_name" content="{{ $b['app_name'] }}">
    <meta property="og:title" content="{{ $m['title'] }}">
    <meta property="og:description" content="{{ $m['description'] }}">
    <meta property="og:url" content="{{ $m['url'] }}">
    <meta property="og:image" content="{{ $m['image'] }}">
    <meta property="og:image:alt" content="{{ $m['title'] }}">
    <meta property="og:locale" content="fr_FR">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="{{ $m['card'] }}">
    <meta name="twitter:title" content="{{ $m['title'] }}">
    <meta name="twitter:description" content="{{ $m['description'] }}">
    <meta name="twitter:image" content="{{ $m['image'] }}">

    @if (!empty($m['jsonld']))
        {{-- Données structurées : permettent à un moteur d'afficher la date
             et le lieu de l'événement directement dans ses résultats. --}}
        <script type="application/ld+json">{!! $m['jsonld'] !!}</script>
    @endif

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="{{ $b['color_primary'] ?? '#272d63' }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $b['app_name'] }}">
    <meta name="application-name" content="{{ $b['app_name'] }}">
    <meta name="msapplication-TileColor" content="{{ $b['color_primary'] ?? '#272d63' }}">
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
    <link rel="apple-touch-startup-image" href="/images/logo.png?v=3">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Branding injecté pour le SPA (évite un flash au chargement) -->
    <script>window.__BRANDING__ = @json($b);</script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Couleurs de marque (surchargent les défauts d'app.css :root) -->
    <style>
        :root {
        @foreach(\App\Models\Setting::brandColorVars() as $var => $val)
            {{ $var }}: {{ $val }};
        @endforeach
        }
    </style>
    @include('partials.analytics')
</head>
<body class="font-sans antialiased">
    <div id="app"></div>
</body>
</html>