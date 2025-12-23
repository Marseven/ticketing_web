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
            padding: 15px;
        }

        .ticket {
            background: white;
            max-width: 650px;
            margin: 0 auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        /* Image de l'événement */
        .event-image-container {
            position: relative;
            width: 100%;
            height: 250px;
            overflow: hidden;
            background: linear-gradient(135deg, #272d63 0%, #4a5098 100%);
        }

        .event-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.95;
        }

        .event-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.3) 70%, transparent 100%);
            padding: 25px;
            color: white;
        }

        .event-overlay h1 {
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .event-overlay p {
            font-size: 16px;
            opacity: 0.95;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        /* Header avec logo si pas d'image */
        .ticket-header {
            background: linear-gradient(135deg, #272d63 0%, #4a5098 100%);
            color: white;
            padding: 35px 25px;
            text-align: center;
            position: relative;
        }

        .logo {
            width: 90px;
            height: auto;
            margin-bottom: 15px;
        }

        .ticket-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: white;
            font-weight: bold;
        }

        .ticket-header p {
            font-size: 15px;
            color: rgba(255,255,255,0.9);
        }

        .ticket-body {
            padding: 25px;
        }

        .event-info {
            border-bottom: 2px dashed #e0e0e0;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 18px;
            align-items: center;
        }

        .info-label {
            font-weight: 600;
            color: #272d63;
            font-size: 14px;
        }

        .info-value {
            color: #333;
            font-size: 14px;
            text-align: right;
        }

        .qr-section {
            text-align: center;
            padding: 25px 0;
            background: #f9f9f9;
            border-radius: 8px;
            margin: 20px 0;
        }

        .qr-section p.scan-instruction {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: 500;
        }

        .qr-section img {
            width: 220px;
            height: 220px;
            margin: 15px auto;
            border: 3px solid #fab511;
            border-radius: 8px;
            padding: 8px;
            background: white;
        }

        .ticket-code {
            font-size: 22px;
            font-weight: bold;
            color: #272d63;
            margin: 12px 0 8px 0;
            letter-spacing: 1px;
        }

        .qr-section p.unique-note {
            color: #999;
            font-size: 11px;
            margin-top: 8px;
        }

        .buyer-info {
            background: #f0f4ff;
            padding: 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #272d63;
        }

        .buyer-info .info-row {
            margin-bottom: 12px;
        }

        .buyer-info .info-row:last-child {
            margin-bottom: 0;
        }

        .warning {
            background: #fff9e6;
            border: 2px solid #fab511;
            padding: 18px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 11px;
            color: #856404;
            line-height: 1.6;
        }

        .warning strong {
            display: block;
            margin-bottom: 8px;
            font-size: 12px;
            color: #272d63;
        }

        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            color: #666;
            font-size: 11px;
            border-top: 1px solid #e0e0e0;
        }

        .footer p {
            margin: 3px 0;
        }

        .price {
            font-size: 26px;
            font-weight: bold;
            color: #fab511;
        }

        .badge {
            display: inline-block;
            background: #fab511;
            color: #272d63;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .date-time-block {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .date-time-block .info-row {
            margin-bottom: 12px;
        }

        .date-time-block .info-row:last-child {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <!-- Image de l'événement si disponible -->
        @if($eventImageBase64)
            <div class="event-image-container">
                <img src="{{ $eventImageBase64 }}" alt="{{ $event->title }}" class="event-image">
                <div class="event-overlay">
                    <h1>{{ $event->title }}</h1>
                    <p>📍 {{ $venue?->name ?? 'À définir' }}</p>
                </div>
            </div>
        @else
            <!-- Header avec logo si pas d'image -->
            <div class="ticket-header">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Primea" class="logo">
                @endif
                <h1>{{ $event->title }}</h1>
                <p>📍 {{ $venue?->name ?? 'À définir' }}</p>
            </div>
        @endif

        <div class="ticket-body">
            <!-- Informations de date et heure -->
            <div class="date-time-block">
                <div class="info-row">
                    <span class="info-label">📅 Date</span>
                    <span class="info-value">
                        @if($schedule)
                            <strong>{{ \Carbon\Carbon::parse($schedule->starts_at)->translatedFormat('l d F Y') }}</strong>
                        @else
                            <strong>À définir</strong>
                        @endif
                    </span>
                </div>

                @if($schedule)
                <div class="info-row">
                    <span class="info-label">🕐 Heure de début</span>
                    <span class="info-value"><strong>{{ \Carbon\Carbon::parse($schedule->starts_at)->format('H:i') }}</strong></span>
                </div>
                @endif

                @if($schedule && $schedule->door_time)
                <div class="info-row">
                    <span class="info-label">🚪 Ouverture des portes</span>
                    <span class="info-value"><strong>{{ \Carbon\Carbon::parse($schedule->door_time)->format('H:i') }}</strong></span>
                </div>
                @endif
            </div>

            <!-- Informations du billet -->
            <div class="event-info">
                <div class="info-row">
                    <span class="info-label">🎫 Catégorie</span>
                    <span class="badge">{{ $ticketType->name }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">💰 Prix</span>
                    <span class="price">{{ number_format($ticketType->price, 0, ',', ' ') }} XAF</span>
                </div>
            </div>

            <!-- Informations du titulaire -->
            <div class="buyer-info">
                <div class="info-row">
                    <span class="info-label">👤 Titulaire</span>
                    <span class="info-value"><strong>{{ $buyer ? $buyer->name : ($ticket->order->guest_name ?? 'Guest') }}</strong></span>
                </div>
                @if($buyer && $buyer->phone)
                <div class="info-row">
                    <span class="info-label">📱 Téléphone</span>
                    <span class="info-value">{{ $buyer->phone }}</span>
                </div>
                @elseif($ticket->order->guest_phone)
                <div class="info-row">
                    <span class="info-label">📱 Téléphone</span>
                    <span class="info-value">{{ $ticket->order->guest_phone }}</span>
                </div>
                @endif
            </div>

            <!-- Section QR Code -->
            <div class="qr-section">
                <p class="scan-instruction"><strong>Présentez ce QR code à l'entrée</strong></p>
                <img src="{{ $qrCodeBase64 }}" alt="QR Code">
                <div class="ticket-code">{{ $ticket->code }}</div>
                <p class="unique-note">Ce QR code est unique et ne peut être scanné qu'une seule fois</p>
            </div>

            <!-- Avertissement -->
            <div class="warning">
                <strong>⚠️ CONDITIONS D'UTILISATION</strong>
                • Ce ticket est strictement personnel et à usage unique<br>
                • Il ne peut être ni vendu ni transféré à autrui<br>
                • Toute tentative de duplication ou falsification entraînera un refus d'entrée<br>
                • Présentez-vous avec une pièce d'identité valide
            </div>
        </div>

        <div class="footer">
            <p><strong>Propulsé par Primea</strong></p>
            <p>{{ config('app.url') }}</p>
            <p style="margin-top: 5px; color: #999;">Téléchargé le {{ \Carbon\Carbon::now()->translatedFormat('d F Y à H:i') }}</p>
        </div>
    </div>
</body>
</html>
