<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        /* Billets physiques — même design que le billet web approuvé :
           JUSTE l'affiche (entière, proportions conservées) + le QR (même hauteur),
           mention noire centrée, logo Primea. Un billet par ligne, à découper. */
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 12px; color: #111827; }
        .batch { font-size: 9px; color: #999; margin-bottom: 10px; }
        .ticket { border: 1px dashed #9ca3af; border-radius: 12px; padding: 12px 12px 8px; margin-bottom: 12px; page-break-inside: avoid; }
        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: middle; padding: 0; }
        .poster-cell { width: 1%; white-space: nowrap; padding-right: 14px; }
        .poster { height: 130px; width: auto; border-radius: 8px; display: block; }
        .poster-empty { height: 130px; width: 130px; border-radius: 8px; background: #272d63; color: #ffffff; text-align: center; font-size: 9px; line-height: 130px; }
        .qr-cell { width: 1%; white-space: nowrap; padding: 0 10px; text-align: center; }
        .qr { width: 130px; height: 130px; display: block; }
        .note { font-size: 6.5px; font-weight: bold; color: #111827; text-align: center; letter-spacing: .04em; text-transform: uppercase; margin-top: 5px; }
        .brand-row td { padding-top: 8px; }
        .ref { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #6b7280; }
        .ref strong { color: #111827; }
        .brand { text-align: right; }
        .brand img { height: 14px; }
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
                            <img class="poster" src="{{ $item['poster'] }}" alt="Affiche">
                        @else
                            <div class="poster-empty">{{ $item['event_title'] }}</div>
                        @endif
                    </td>
                    <td class="qr-cell">
                        <img class="qr" src="{{ $item['qr'] }}" alt="QR Code">
                        <div class="note">QR code unique et personnel — ne pas le partager</div>
                    </td>
                    <td></td>
                </tr>
                <tr class="brand-row">
                    <td class="ref">@if (!empty($item['type']))<strong>{{ $item['type'] }}</strong> · @endif{{ $item['code'] }}</td>
                    <td colspan="2" class="brand">
                        @if (!empty($logo))
                            <img src="{{ $logo }}" alt="Primea">
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    @endforeach
</body>
</html>
