<!DOCTYPE html>
{{--
    Liste des billets vendus d'un événement, en PDF.

    Pensée pour être IMPRIMÉE et emportée à l'entrée : paysage, en-tête répété
    sur chaque page, et les colonnes qui servent au contrôle placées à gauche.
    Le code du billet vient donc en premier — c'est par lui qu'on cherche.
--}}
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Billets vendus — {{ $event->title }}</title>
    <style>
        @page { margin: 14mm 10mm; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5pt;
            color: #1a1a1a;
        }

        h1 { font-size: 14pt; margin: 0 0 2mm; color: #272d63; }

        .sous-titre { font-size: 9pt; color: #555; margin-bottom: 4mm; }

        .resume {
            margin-bottom: 4mm;
            padding: 2.5mm 3mm;
            background: #f4f5f9;
            border-left: 3px solid #fab511;
        }
        .resume span { margin-right: 6mm; }
        .resume strong { color: #272d63; }

        table { width: 100%; border-collapse: collapse; }

        thead { display: table-header-group; }

        th {
            background: #272d63;
            color: #fff;
            text-align: left;
            padding: 1.6mm 1.5mm;
            font-size: 7.5pt;
            font-weight: bold;
        }

        td {
            padding: 1.4mm 1.5mm;
            border-bottom: 0.3pt solid #dcdce4;
            font-size: 7.5pt;
        }

        tr:nth-child(even) td { background: #fafafc; }

        .montant { text-align: right; white-space: nowrap; }
        .code { font-family: DejaVu Sans Mono, monospace; font-size: 7pt; }
        .entre { color: #0b7a3b; font-weight: bold; }

        .pied {
            position: fixed;
            bottom: -8mm;
            left: 0;
            right: 0;
            font-size: 7pt;
            color: #888;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="pied">
        {{ $marque }} — document généré le {{ $genereLe }}
    </div>

    <h1>Billets vendus — {{ $event->title }}</h1>

    <div class="sous-titre">
        @if ($event->venue?->name){{ $event->venue->name }} · @endif
        @if ($premiereDate){{ $premiereDate }}@endif
    </div>

    <div class="resume">
        <span><strong>{{ $total }}</strong> billet(s)</span>
        <span><strong>{{ $entres }}</strong> entré(s)</span>
        <span>Recette : <strong>{{ number_format($recette, 0, ',', ' ') }} {{ $devise }}</strong></span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Catégorie</th>
                <th>Date</th>
                <th>Acheteur</th>
                <th>Tél. compte</th>
                <th>Tél. paiement</th>
                <th>Commande</th>
                <th class="montant">Prix payé</th>
                <th>Origine</th>
                <th>Statut</th>
                <th>Scanné le</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($billets as $billet)
                <tr>
                    <td class="code">{{ $billet['code'] }}</td>
                    <td>{{ $billet['categorie'] }}</td>
                    <td>{{ $billet['date'] }}</td>
                    <td>{{ $billet['acheteur'] }}</td>
                    <td>{{ $billet['telephone_compte'] }}</td>
                    <td>{{ $billet['telephone_paiement'] }}</td>
                    <td class="code">{{ $billet['commande'] }}</td>
                    <td class="montant">{{ number_format($billet['prix'], 0, ',', ' ') }}</td>
                    <td>{{ $billet['origine'] }}</td>
                    <td class="{{ $billet['statut'] === 'Entré' ? 'entre' : '' }}">{{ $billet['statut'] }}</td>
                    <td>{{ $billet['scanne_le'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="padding: 6mm; text-align: center; color: #777;">
                        Aucun billet vendu pour cet événement.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
