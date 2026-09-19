<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 12px; color: #272d63; }
        .batch { font-size: 9px; color: #999; margin-bottom: 10px; }
        .ticket {
            border: 1px solid #272d63;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }
        .ticket table { width: 100%; border-collapse: collapse; }
        .poster-cell { width: 38%; vertical-align: middle; background: #272d63; padding: 0; }
        .poster-cell img { width: 100%; display: block; }
        .poster-empty { color: #ffffff; text-align: center; font-size: 10px; padding: 46px 6px; }
        .info-cell { vertical-align: top; padding: 10px 12px; }
        .ev-title { font-size: 14px; font-weight: bold; color: #272d63; text-transform: uppercase; line-height: 1.15; margin: 0 0 4px; }
        .ev-meta { font-size: 9px; color: #555; margin-bottom: 8px; }
        .ref { font-size: 10px; font-family: DejaVu Sans Mono, monospace; letter-spacing: 1px; color: #272d63; text-align: center; margin-bottom: 3px; }
        .qr img { width: 100px; height: 100px; display: block; margin: 0 auto; }
        .warn { font-size: 7.5px; color: #c0392b; text-align: center; margin-top: 5px; text-transform: uppercase; letter-spacing: .5px; }
        .brand { text-align: center; margin-top: 6px; }
        .brand img { width: 42px; }
    </style>
</head>
<body>
    <div class="batch">Lot : {{ $batch }} — {{ count($items) }} billet(s)</div>

    @foreach ($items as $item)
        <div class="ticket">
            <table>
                <tr>
                    <td class="poster-cell">
                        @if (!empty($item['poster']))
                            <img src="{{ $item['poster'] }}" alt="">
                        @else
                            <div class="poster-empty">{{ $item['event_title'] }}</div>
                        @endif
                    </td>
                    <td class="info-cell">
                        <div class="ev-title">{{ $item['event_title'] }}</div>
                        <div class="ev-meta">
                            @if ($item['date']) {{ \Illuminate\Support\Carbon::parse($item['date'])->format('d/m/Y H:i') }} @endif
                            @if (!empty($item['venue'])) · {{ $item['venue'] }} @endif
                            @if ($item['type']) · {{ $item['type'] }} @endif
                        </div>
                        <div class="ref">{{ $item['code'] }}</div>
                        <div class="qr"><img src="{{ $item['qr'] }}" alt="QR"></div>
                        <div class="warn">QR code unique et personnel — ne pas partager</div>
                        @if (!empty($logo))
                            <div class="brand"><img src="{{ $logo }}" alt="Primea"></div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    @endforeach
</body>
</html>
