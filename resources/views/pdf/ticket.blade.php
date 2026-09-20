<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        /* Billet PDF — même design que le billet web approuvé :
           JUSTE l'affiche (entière, proportions conservées) + le QR (même hauteur),
           mention noire centrée, logo Primea. Pas de titre/date/prix (c'est sur l'affiche). */
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 18px; color: #111827; background: #ffffff; }
        .card { border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px 16px 12px; }
        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: middle; padding: 0; }
        .poster-cell { width: 1%; white-space: nowrap; padding-right: 16px; }
        .poster { height: 140px; width: auto; border-radius: 8px; display: block; }
        .poster-empty { height: 140px; width: 140px; border-radius: 8px; background: #272d63; color: #ffffff; text-align: center; font-size: 10px; line-height: 140px; }
        .qr-cell { width: 1%; white-space: nowrap; padding: 0 12px; text-align: center; }
        .qr { width: 140px; height: 140px; display: block; }
        .note { font-size: 7px; font-weight: bold; color: #111827; text-align: center; letter-spacing: .04em; text-transform: uppercase; margin-top: 6px; }
        .brand-row td { text-align: right; padding-top: 10px; }
        .brand-row img { height: 16px; }
        .ref { font-family: DejaVu Sans Mono, monospace; font-size: 8px; color: #6b7280; text-align: left; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="card">
        <table>
            <tr>
                <td class="poster-cell">
                    @if (!empty($eventImageBase64))
                        <img class="poster" src="{{ $eventImageBase64 }}" alt="Affiche">
                    @else
                        <div class="poster-empty">Affiche</div>
                    @endif
                </td>
                <td class="qr-cell">
                    <img class="qr" src="{{ $qrCodeBase64 }}" alt="QR Code">
                    <div class="note">QR code unique et personnel — ne pas le partager</div>
                </td>
                <td></td>
            </tr>
            <tr class="brand-row">
                <td class="ref">{{ $ticket->code }}</td>
                <td colspan="2">
                    @if (!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" alt="Primea">
                    @endif
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
