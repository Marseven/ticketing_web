<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture - {{ $order->reference }}</title>
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

        .invoice {
            background: white;
            max-width: 700px;
            margin: 0 auto;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .invoice-header {
            background: linear-gradient(135deg, #272d63 0%, #1a1e47 100%);
            color: white;
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            flex: 1;
        }

        .logo {
            width: 100px;
            height: auto;
            margin-bottom: 10px;
        }

        .invoice-title {
            flex: 1;
            text-align: right;
        }

        .invoice-title h1 {
            font-size: 32px;
            margin-bottom: 5px;
        }

        .invoice-title p {
            font-size: 14px;
            opacity: 0.9;
        }

        .invoice-body {
            padding: 30px;
        }

        .section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #272d63;
            margin-bottom: 15px;
            border-bottom: 2px solid #fab511;
            padding-bottom: 5px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: bold;
            color: #666;
        }

        .info-value {
            color: #333;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .items-table th {
            background: #f8f9fa;
            color: #272d63;
            font-weight: bold;
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid #dee2e6;
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }

        .total-row.grand-total {
            font-size: 18px;
            font-weight: bold;
            color: #272d63;
            border-top: 2px solid #272d63;
            margin-top: 10px;
            padding-top: 15px;
        }

        .grand-total .amount {
            color: #fab511;
            font-size: 24px;
        }

        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            color: #666;
            font-size: 12px;
        }

        .footer p {
            margin: 5px 0;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .thank-you {
            background: #e7f3ff;
            border: 2px solid #2196f3;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            text-align: center;
            color: #1976d2;
        }

        .thank-you strong {
            display: block;
            font-size: 16px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="invoice">
        <div class="invoice-header">
            <div class="logo-section">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Primea" class="logo">
                @endif
                <p style="font-size: 12px; opacity: 0.8;">Système de billetterie</p>
            </div>
            <div class="invoice-title">
                <h1>FACTURE</h1>
                <p>{{ $order->reference }}</p>
            </div>
        </div>

        <div class="invoice-body">
            <!-- Order Information -->
            <div class="section">
                <div class="section-title">Informations de la commande</div>
                <div class="info-row">
                    <span class="info-label">Date de commande:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i:s') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Référence:</span>
                    <span class="info-value">{{ $order->reference }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut:</span>
                    <span class="status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                </div>
                @if($order->payment_method)
                <div class="info-row">
                    <span class="info-label">Moyen de paiement:</span>
                    <span class="info-value">{{ ucfirst($order->payment_method) }}</span>
                </div>
                @endif
            </div>

            <!-- Customer Information -->
            <div class="section">
                <div class="section-title">Informations client</div>
                <div class="info-row">
                    <span class="info-label">Nom:</span>
                    <span class="info-value">{{ $order->user ? $order->user->name : $order->guest_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $order->user ? $order->user->email : $order->guest_email }}</span>
                </div>
                @if($order->guest_phone)
                <div class="info-row">
                    <span class="info-label">Téléphone:</span>
                    <span class="info-value">{{ $order->guest_phone }}</span>
                </div>
                @endif
            </div>

            <!-- Event Information -->
            <div class="section">
                <div class="section-title">Détails de l'événement</div>
                <div class="info-row">
                    <span class="info-label">Événement:</span>
                    <span class="info-value">{{ $order->event->title }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Lieu:</span>
                    <span class="info-value">{{ $order->event->venue_name ?? 'À définir' }}</span>
                </div>
                @if($order->schedule)
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($order->schedule->starts_at)->translatedFormat('l d F Y à H:i') }}</span>
                </div>
                @endif
            </div>

            <!-- Tickets -->
            <div class="section">
                <div class="section-title">Billets achetés</div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Type de billet</th>
                            <th class="text-center">Quantité</th>
                            <th class="text-right">Prix unitaire</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $ticketsByType = $order->tickets->groupBy('ticket_type_id');
                        @endphp
                        @foreach($ticketsByType as $typeId => $tickets)
                            @php
                                $ticketType = $tickets->first()->ticketType;
                                $quantity = $tickets->count();
                                $unitPrice = (float)$ticketType->price;
                                $total = $quantity * $unitPrice;
                            @endphp
                            <tr>
                                <td>{{ $ticketType->name }}</td>
                                <td class="text-center">{{ $quantity }}</td>
                                <td class="text-right">{{ number_format($unitPrice, 0, ',', ' ') }} XAF</td>
                                <td class="text-right">{{ number_format($total, 0, ',', ' ') }} XAF</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="totals-section">
                <div class="total-row">
                    <span>Sous-total:</span>
                    <span>{{ number_format($order->subtotal_amount, 0, ',', ' ') }} XAF</span>
                </div>
                @if($order->fees_amount > 0)
                <div class="total-row">
                    <span>Frais de service:</span>
                    <span>{{ number_format($order->fees_amount, 0, ',', ' ') }} XAF</span>
                </div>
                @endif
                @if($order->tax_amount > 0)
                <div class="total-row">
                    <span>Taxes:</span>
                    <span>{{ number_format($order->tax_amount, 0, ',', ' ') }} XAF</span>
                </div>
                @endif
                <div class="total-row grand-total">
                    <span>Total payé:</span>
                    <span class="amount">{{ number_format($order->total_amount, 0, ',', ' ') }} XAF</span>
                </div>
            </div>

            <!-- Thank you message -->
            @if($order->status === 'confirmed')
            <div class="thank-you">
                <strong>Merci pour votre achat !</strong>
                Vos billets ont été envoyés par email. Présentez-les à l'entrée de l'événement.
            </div>
            @endif
        </div>

        <div class="footer">
            <p><strong>Primea - Système de billetterie</strong></p>
            <p>{{ config('app.url') }}</p>
            <p>Cette facture a été générée électroniquement et ne nécessite pas de signature.</p>
        </div>
    </div>
</body>
</html>
