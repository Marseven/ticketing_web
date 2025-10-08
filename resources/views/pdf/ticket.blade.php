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
            padding: 20px;
        }

        .ticket {
            background: white;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .ticket-header {
            background: linear-gradient(135deg, #272d63 0%, #1a1e47 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .logo {
            width: 100px;
            height: auto;
            margin-bottom: 15px;
        }

        .ticket-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .ticket-header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .ticket-body {
            padding: 30px;
        }

        .event-info {
            border-bottom: 2px dashed #ddd;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .info-label {
            font-weight: bold;
            color: #272d63;
        }

        .info-value {
            color: #333;
        }

        .qr-section {
            text-align: center;
            padding: 20px 0;
        }

        .qr-section img {
            width: 200px;
            height: 200px;
            margin: 20px auto;
        }

        .ticket-code {
            font-size: 24px;
            font-weight: bold;
            color: #272d63;
            margin: 10px 0;
        }

        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 12px;
            color: #856404;
        }

        .warning strong {
            display: block;
            margin-bottom: 5px;
        }

        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            color: #666;
            font-size: 12px;
        }

        .price {
            font-size: 28px;
            font-weight: bold;
            color: #fab511;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="ticket-header">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Primea" class="logo">
            @endif
            <h1>{{ $event->title }}</h1>
            <p>{{ $venue?->name ?? 'À définir' }}</p>
        </div>

        <div class="ticket-body">
            <div class="event-info">
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span class="info-value">
                        @if($schedule)
                            {{ \Carbon\Carbon::parse($schedule->starts_at)->translatedFormat('l d F Y') }}
                        @else
                            À définir
                        @endif
                    </span>
                </div>

                @if($schedule && $schedule->door_time)
                <div class="info-row">
                    <span class="info-label">Heure d'ouverture:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($schedule->door_time)->format('H:i') }}</span>
                </div>
                @endif

                <div class="info-row">
                    <span class="info-label">Catégorie:</span>
                    <span class="info-value">{{ $ticketType->name }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Prix:</span>
                    <span class="price">{{ number_format($ticketType->price, 0, ',', ' ') }} XAF</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Titulaire:</span>
                    <span class="info-value">{{ $buyer->name }}</span>
                </div>
            </div>

            <div class="qr-section">
                <p style="color: #666; margin-bottom: 10px;">Présentez ce QR code à l'entrée</p>
                <img src="{{ $qrCodeBase64 }}" alt="QR Code">
                <div class="ticket-code">{{ $ticket->code }}</div>
                <p style="color: #999; font-size: 12px;">Ce QR code est unique et ne peut être scanné qu'une seule fois</p>
            </div>

            <div class="warning">
                <strong>⚠️ ATTENTION</strong>
                CE TICKET EST STRICTEMENT PERSONNEL ET À USAGE UNIQUE.
                IL NE PEUT ÊTRE NI VENDU NI DONNÉ À AUTRUI SOUS PEINE D'ÊTRE REFUSÉ À L'ENTRÉE.
            </div>
        </div>

        <div class="footer">
            <p>Propulsé par Primea</p>
            <p>{{ config('app.url') }}</p>
        </div>
    </div>
</body>
</html>
