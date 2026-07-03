<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Notifications\EventApproved;
use App\Notifications\EventRejected;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EventApprovalController extends Controller
{
    /**
     * Liste des événements filtrable par statut d'approbation.
     * Défaut : ceux en attente de validation.
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('approval_status', 'pending');

        $query = Event::with(['organizer:id,name,default_commission_percentage', 'category:id,name'])
            ->orderBy('created_at', 'desc');

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('approval_status', $status);
        }

        $events = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    /**
     * Approuver un événement, en fixant éventuellement le taux de commission.
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $event = Event::find($id);
        if (!$event) {
            return response()->json(['success' => false, 'message' => 'Événement introuvable'], 404);
        }

        $validator = Validator::make($request->all(), [
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->filled('commission_percentage')) {
            $event->commission_percentage = $request->input('commission_percentage');
        }

        $event->approval_status = 'approved';
        $event->approved_by = $request->user()->id;
        $event->approved_at = now();
        $event->rejection_reason = null;
        $event->save();

        $this->notifyOrganizer($event, EventApproved::class, 'EventApproved');

        return response()->json([
            'success' => true,
            'message' => 'Événement approuvé',
            'data' => $event->fresh(),
        ]);
    }

    /**
     * Rejeter un événement avec un motif.
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $event = Event::find($id);
        if (!$event) {
            return response()->json(['success' => false, 'message' => 'Événement introuvable'], 404);
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Un motif de rejet est requis',
                'errors' => $validator->errors(),
            ], 422);
        }

        $event->approval_status = 'rejected';
        $event->approved_by = $request->user()->id;
        $event->approved_at = now();
        $event->rejection_reason = $request->input('rejection_reason');
        $event->save();

        $this->notifyOrganizer($event, EventRejected::class, 'EventRejected');

        return response()->json([
            'success' => true,
            'message' => 'Événement rejeté',
            'data' => $event->fresh(),
        ]);
    }

    /**
     * Mettre à jour uniquement le taux de commission d'un événement.
     */
    public function setCommission(Request $request, string $id): JsonResponse
    {
        $event = Event::find($id);
        if (!$event) {
            return response()->json(['success' => false, 'message' => 'Événement introuvable'], 404);
        }

        $validator = Validator::make($request->all(), [
            'commission_percentage' => 'required|numeric|min:0|max:100',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Taux de commission invalide (0-100)',
                'errors' => $validator->errors(),
            ], 422);
        }

        $event->commission_percentage = $request->input('commission_percentage');
        $event->save();

        return response()->json([
            'success' => true,
            'message' => 'Commission mise à jour',
            'data' => $event->fresh(),
        ]);
    }

    /**
     * Notifier tous les membres de l'organisateur, sans casser le flux
     * si l'envoi échoue.
     */
    private function notifyOrganizer(Event $event, string $notificationClass, string $label): void
    {
        try {
            $organizer = $event->organizer;
            if (!$organizer) {
                return;
            }
            foreach ($organizer->users()->get() as $user) {
                $user->notify(new $notificationClass($event));
            }
        } catch (\Exception $e) {
            Log::error("Erreur envoi notification {$label}", [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
