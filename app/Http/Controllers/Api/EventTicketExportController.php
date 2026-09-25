<?php

namespace App\Http\Controllers\Api;

use App\Exports\EventTicketsExport;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Export de la liste des billets vendus d'un événement, en tableur ou en PDF.
 *
 * Deux usages distincts, d'où deux formats. Le tableur sert à rapprocher les
 * ventes et à retrouver un acheteur ; le PDF s'imprime et s'emporte à
 * l'entrée, quand le réseau manque ou que le scanner tombe en panne.
 *
 * Un organisateur n'exporte que SES événements. C'est un fichier nominatif —
 * noms, adresses, numéros de téléphone — et le laisser accessible à tout
 * compte reviendrait à publier le fichier clients de quelqu'un d'autre.
 */
class EventTicketExportController extends Controller
{
    public function __invoke(Request $request, string $event): Response
    {
        $request->validate([
            'format' => 'nullable|string|in:xlsx,pdf,csv',
        ], [
            'format.in' => 'Format inconnu : choisissez xlsx, csv ou pdf.',
        ]);

        // L'identifiant OU le slug : l'écran d'administration connaît l'un,
        // l'espace organisateur l'autre.
        $evenement = Event::with('venue')
            ->where(is_numeric($event) ? 'id' : 'slug', $event)
            ->firstOrFail();

        if (! $this->peutExporter($request, $evenement)) {
            abort(403, "Vous n'avez pas accès aux ventes de cet événement.");
        }

        $format = $request->query('format', 'xlsx');
        $nom = $this->nomDeFichier($evenement, $format);

        return $format === 'pdf'
            ? $this->pdf($evenement, $nom)
            : Excel::download(new EventTicketsExport($evenement), $nom);
    }

    /**
     * L'administration voit tout ; un organisateur, ses événements seulement.
     */
    private function peutExporter(Request $request, Event $evenement): bool
    {
        $utilisateur = $request->user();

        if ($utilisateur->isPlatformAdmin()) {
            return true;
        }

        return $utilisateur->organizers->pluck('id')->contains($evenement->organizer_id);
    }

    private function pdf(Event $evenement, string $nom): Response
    {
        $billets = EventTicketsExport::query($evenement)->get();

        $lignes = $billets->map(fn ($billet) => [
            'code' => $billet->code,
            'categorie' => $billet->ticketType?->name ?? '—',
            'date' => $billet->schedule?->starts_at?->format('d/m/Y H:i') ?? '—',
            'acheteur' => $billet->buyer?->name ?? $billet->order?->guest_name ?? '—',
            'telephone_compte' => $billet->buyer?->phone ?? $billet->order?->guest_phone ?? '—',
            'telephone_paiement' => EventTicketsExport::payerPhone($billet),
            'commande' => $billet->order?->reference ?? '—',
            'prix' => (float) ($billet->price_paid ?? 0),
            'origine' => EventTicketsExport::origine($billet),
            'statut' => EventTicketsExport::statut($billet),
            'scanne_le' => $billet->used_at?->format('d/m/Y H:i') ?? '—',
            'scanne_par' => EventTicketsExport::scannePar($billet),
        ]);

        $pdf = Pdf::loadView('pdf.event-tickets', [
            'event' => $evenement,
            'billets' => $lignes,
            'total' => $billets->count(),
            'entres' => $billets->where('status', 'used')->count(),
            'recette' => $lignes->sum('prix'),
            'devise' => $billets->first()?->order?->currency ?? 'XAF',
            'premiereDate' => $evenement->schedules()->orderBy('starts_at')->value('starts_at')
                ? \Illuminate\Support\Carbon::parse(
                    $evenement->schedules()->orderBy('starts_at')->value('starts_at')
                )->format('d/m/Y H:i')
                : null,
            'marque' => Setting::branding()['app_name'] ?? 'Primea',
            'genereLe' => now()->format('d/m/Y à H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($nom);
    }

    /**
     * Un nom de fichier lisible et sans surprise : l'organisateur en reçoit
     * plusieurs et doit pouvoir les distinguer sans les ouvrir.
     */
    private function nomDeFichier(Event $evenement, string $format): string
    {
        $titre = Str::slug($evenement->title) ?: 'evenement';

        return "billets-{$titre}-" . now()->format('Y-m-d') . ".{$format}";
    }
}
