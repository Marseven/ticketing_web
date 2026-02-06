<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket - {{ $ticket->code }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            background: #f5f5f5;
            padding: 10px;
        }

        .ticket {
            background: white;
            max-width: 700px;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* ===== SECTION IMAGE EVENEMENT ===== */
        .event-image-section {
            width: 100%;
            height: 280px;
            overflow: hidden;
            background: linear-gradient(135deg, #272d63 0%, #4a5098 100%);
            position: relative;
        }

        .event-image-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Header alternatif sans image */
        .event-header-no-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #272d63 0%, #4a5098 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            padding: 20px;
        }

        .event-header-no-image .logo {
            width: 80px;
            height: auto;
            margin-bottom: 15px;
        }

        .event-header-no-image h1 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .event-header-no-image p {
            font-size: 14px;
            opacity: 0.9;
        }

        /* ===== SECTION INFORMATIONS ===== */
        .ticket-info-section {
            padding: 25px 30px;
            position: relative;
        }

        /* Code du ticket en haut à droite */
        .ticket-number {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 14px;
            font-weight: bold;
            color: #dc2626;
            font-family: 'Courier New', monospace;
        }

        /* Layout 2 colonnes */
        .info-content {
            display: table;
            width: 100%;
        }

        .info-left {
            display: table-cell;
            width: 55%;
            vertical-align: top;
            padding-right: 20px;
        }

        .info-right {
            display: table-cell;
            width: 45%;
            vertical-align: top;
            text-align: center;
        }

        /* Titre événement */
        .event-title {
            font-size: 22px;
            font-weight: bold;
            color: #272d63;
            text-transform: uppercase;
            line-height: 1.2;
            margin-bottom: 15px;
            padding-right: 80px; /* Espace pour le numéro de ticket */
        }

        /* Détails événement */
        .event-details {
            margin-bottom: 20px;
        }

        .event-details p {
            font-size: 14px;
            color: #374151;
            margin-bottom: 6px;
            line-height: 1.4;
        }

        .event-details .label {
            color: #6b7280;
        }

        .event-details .value {
            font-weight: 600;
            color: #1f2937;
        }

        /* Prix */
        .ticket-price {
            font-size: 36px;
            font-weight: bold;
            color: #dc2626;
            margin: 20px 0;
            letter-spacing: -1px;
        }

        /* Section avertissement */
        .warning-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        .warning-title {
            font-size: 11px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }

        .warning-text {
            font-size: 10px;
            color: #dc2626;
            line-height: 1.3;
            letter-spacing: 0.3px;
        }

        /* Logo Primea */
        .primea-logo {
            margin-top: 20px;
        }

        .primea-logo img {
            height: 35px;
            width: auto;
        }

        .primea-logo-text {
            font-size: 24px;
            font-weight: bold;
            color: #272d63;
        }

        .primea-logo-text span {
            color: #fab511;
        }

        .primea-tagline {
            font-size: 8px;
            color: #6b7280;
            margin-top: 2px;
        }

        /* ===== SECTION QR CODE ===== */
        .qr-code-container {
            padding: 10px;
        }

        .qr-code-container img {
            width: 200px;
            height: 200px;
            border: none;
        }

        .qr-unique-text {
            margin-top: 12px;
            font-size: 12px;
        }

        .qr-unique-text .red {
            color: #dc2626;
            font-weight: 600;
        }

        .qr-unique-text .gray {
            color: #6b7280;
            font-size: 11px;
        }

        /* ===== INFORMATIONS TITULAIRE ===== */
        .buyer-section {
            background: #f8fafc;
            padding: 15px 30px;
            border-top: 2px dashed #e5e7eb;
        }

        .buyer-section-title {
            font-size: 11px;
            font-weight: 600;
            color: #272d63;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .buyer-info-row {
            display: table;
            width: 100%;
        }

        .buyer-info-item {
            display: table-cell;
            width: 50%;
            font-size: 12px;
        }

        .buyer-info-item .label {
            color: #6b7280;
        }

        .buyer-info-item .value {
            font-weight: 600;
            color: #1f2937;
        }

        /* ===== FOOTER ===== */
        .ticket-footer {
            background: #272d63;
            color: white;
            padding: 12px 30px;
            font-size: 10px;
            text-align: center;
        }

        .ticket-footer p {
            margin: 2px 0;
            opacity: 0.9;
        }

        .ticket-code-footer {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <!-- Section Image Événement -->
        @if($eventImageBase64)
            <div class="event-image-section">
                <img src="{{ $eventImageBase64 }}" alt="{{ $event->title }}">
            </div>
        @else
            <div class="event-header-no-image">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Primea" class="logo">
                @endif
                <h1>{{ $event->title }}</h1>
                <p>{{ $venue?->name ?? 'Lieu à définir' }}</p>
            </div>
        @endif

        <!-- Section Informations Principales -->
        <div class="ticket-info-section">
            <!-- Code du ticket -->
            <div class="ticket-number">{{ $ticket->code }}</div>

            <div class="info-content">
                <!-- Colonne Gauche : Détails -->
                <div class="info-left">
                    <h2 class="event-title">{{ $event->title }}</h2>

                    <div class="event-details">
                        @if($schedule)
                            <p>
                                <span class="value">{{ strtoupper(\Carbon\Carbon::parse($schedule->starts_at)->translatedFormat('l d F Y')) }}</span>
                            </p>
                            @if($schedule->door_time)
                                <p>
                                    <span class="label">Ouverture des portes : </span>
                                    <span class="value">{{ \Carbon\Carbon::parse($schedule->door_time)->format('H\hi') }}</span>
                                </p>
                            @endif
                        @endif
                        <p>
                            <span class="label">Lieu : </span>
                            <span class="value">{{ $venue?->name ?? 'À définir' }}</span>
                        </p>
                        <p>
                            <span class="label">Catégorie : </span>
                            <span class="value">{{ $ticketType->name }}</span>
                        </p>
                    </div>

                    <div class="ticket-price">
                        {{ number_format($ticketType->price, 0, ',', '.') }} FCFA
                    </div>

                    <div class="warning-section">
                        <p class="warning-title">** ATTENTION **</p>
                        <p class="warning-text">
                            Ce ticket est strictement personnel et à usage<br>
                            unique. Tâchez de ne le remettre à personne.
                        </p>
                    </div>

                    <div class="primea-logo">
                        @if($logoBase64)
                            <img src="{{ $logoBase64 }}" alt="Primea">
                        @else
                            <div class="primea-logo-text">Prim<span>e</span>a</div>
                        @endif
                        <p class="primea-tagline">Simple, Rapide et Sécurisée</p>
                    </div>
                </div>

                <!-- Colonne Droite : QR Code -->
                <div class="info-right">
                    <div class="qr-code-container">
                        <img src="{{ $qrCodeBase64 }}" alt="QR Code">
                    </div>
                    <div class="qr-unique-text">
                        <p class="red">Ce QR Code est unique</p>
                        <p class="gray">et ne peut être scanné qu'une seule fois</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Titulaire -->
        <div class="buyer-section">
            <p class="buyer-section-title">Informations du titulaire</p>
            <div class="buyer-info-row">
                <div class="buyer-info-item">
                    <span class="label">Nom : </span>
                    <span class="value">{{ $buyer ? $buyer->name : ($ticket->order->guest_name ?? 'Non renseigné') }}</span>
                </div>
                <div class="buyer-info-item">
                    <span class="label">Téléphone : </span>
                    <span class="value">
                        @if($buyer && $buyer->phone)
                            {{ $buyer->phone }}
                        @elseif($ticket->order->guest_phone)
                            {{ $ticket->order->guest_phone }}
                        @else
                            Non renseigné
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="ticket-footer">
            <p class="ticket-code-footer">{{ $ticket->code }}</p>
            <p>Téléchargé le {{ \Carbon\Carbon::now()->translatedFormat('d F Y à H:i') }} | {{ config('app.url') }}</p>
        </div>
    </div>
</body>
</html>
