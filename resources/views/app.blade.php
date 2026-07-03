<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>MyTicketO - Plateforme d'événements</title>
    <meta name="description" content="MyTicketO - Plateforme de billetterie en ligne pour événements au Gabon. Achetez vos billets en ligne facilement.">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#004B5E">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MyTicketO">
    <meta name="application-name" content="MyTicketO">
    <meta name="msapplication-TileColor" content="#004B5E">
    <meta name="msapplication-tap-highlight" content="no">
    <meta name="format-detection" content="telephone=no">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/images/ico.png">
    <link rel="shortcut icon" type="image/png" href="/images/ico.png">
    <link rel="apple-touch-icon" href="/images/ico.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/images/ico.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/ico.png">
    <link rel="apple-touch-icon" sizes="167x167" href="/images/ico.png">

    <!-- Splash Screen for iOS -->
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-startup-image" href="/images/logo.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div id="app"></div>
</body>
</html>