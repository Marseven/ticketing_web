<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 0; }
        .grid { width: 100%; }
        .ticket {
            display: inline-block;
            width: 46%;
            border: 1px dashed #999;
            border-radius: 8px;
            margin: 1%;
            padding: 10px;
            text-align: center;
            vertical-align: top;
            page-break-inside: avoid;
        }
        .ticket .event { font-size: 12px; font-weight: bold; color: #004B5E; margin-bottom: 4px; }
        .ticket .meta { font-size: 9px; color: #555; margin-bottom: 6px; }
        .ticket img { width: 130px; height: 130px; }
        .ticket .code { font-size: 10px; font-family: DejaVu Sans Mono, monospace; margin-top: 4px; letter-spacing: 1px; }
        .ticket .ad { font-size: 8px; color: #999; margin-top: 2px; }
        .batch { font-size: 9px; color: #999; padding: 6px 12px; }
    </style>
</head>
<body>
    <div class="batch">Lot : {{ $batch }} — {{ count($items) }} billet(s)</div>
    <div class="grid">
        @foreach ($items as $item)
            <div class="ticket">
                <div class="event">{{ $item['event_title'] }}</div>
                <div class="meta">
                    @if ($item['type']) {{ $item['type'] }} @endif
                    @if ($item['date']) · {{ \Illuminate\Support\Carbon::parse($item['date'])->format('d/m/Y H:i') }} @endif
                </div>
                <img src="{{ $item['qr'] }}" alt="QR">
                <div class="code">{{ $item['code'] }}</div>
                <div class="ad">MyTicketO</div>
            </div>
        @endforeach
    </div>
</body>
</html>
