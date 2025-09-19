<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organizer;
use App\Models\OrganizerBalance;
use App\Models\Payout;
use App\Services\PayoutService;
use App\Services\ShapPayoutService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PayoutController extends Controller
{
    private PayoutService $payoutService;
    private ShapPayoutService $shapPayoutService;

    public function __construct(PayoutService $payoutService, ShapPayoutService $shapPayoutService)
    {
        $this->payoutService = $payoutService;
        $this->shapPayoutService = $shapPayoutService;
    }

    /**
     * Liste des payouts avec filtres
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payout::with(['organizer'])
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->filled('organizer_id')) {
            $query->where('organizer_id', $request->organizer_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('gateway')) {
            $query->where('gateway', $request->gateway);
        }

        if ($request->filled('is_automatic')) {
            $query->where('is_automatic', $request->boolean('is_automatic'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payouts = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'payouts' => $payouts->items(),
                'pagination' => [
                    'current_page' => $payouts->currentPage(),
                    'last_page' => $payouts->lastPage(),
                    'per_page' => $payouts->perPage(),
                    'total' => $payouts->total(),
                ]
            ]
        ]);
    }

    /**
     * Détails d'un payout
     */
    public function show(Payout $payout): JsonResponse
    {
        $payout->load(['organizer']);

        return response()->json([
            'success' => true,
            'data' => [
                'payout' => $payout,
                'gateway_display_name' => $payout->gateway_display_name,
                'status_display_name' => $payout->status_display_name,
                'payout_type_display_name' => $payout->payout_type_display_name,
            ]
        ]);
    }

    /**
     * Créer un payout manuel
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'organizer_id' => 'required|exists:organizers,id',
            'gateway' => 'required|in:airtelmoney,moovmoney',
            'amount' => 'required|numeric|min:100',
            'phone_number' => 'required|string|size:9',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $organizer = Organizer::findOrFail($request->organizer_id);

            $result = $this->payoutService->createManualPayout(
                $organizer,
                $request->gateway,
                $request->amount,
                $request->phone_number
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payout manuel créé avec succès',
                    'data' => ['payout' => $result['payout']]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);

        } catch (\Exception $e) {
            Log::error('Erreur création payout manuel admin', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la création du payout'
            ], 500);
        }
    }

    /**
     * Vérifier le statut d'un payout
     */
    public function checkStatus(Payout $payout): JsonResponse
    {
        try {
            $result = $this->payoutService->checkPayoutStatus($payout);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Statut vérifié' : $result['message'],
                'data' => $result['success'] ? [
                    'current_status' => $result['current_status'],
                    'is_final' => $result['is_final'],
                ] : null
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur vérification statut payout admin', [
                'payout_id' => $payout->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la vérification'
            ], 500);
        }
    }

    /**
     * Liste des soldes des organisateurs
     */
    public function balances(Request $request): JsonResponse
    {
        $query = OrganizerBalance::with(['organizer'])
            ->orderBy('balance', 'desc');

        if ($request->filled('organizer_id')) {
            $query->where('organizer_id', $request->organizer_id);
        }

        if ($request->filled('gateway')) {
            $query->where('gateway', $request->gateway);
        }

        if ($request->filled('min_balance')) {
            $query->where('balance', '>=', $request->min_balance);
        }

        $balances = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'balances' => $balances->items(),
                'pagination' => [
                    'current_page' => $balances->currentPage(),
                    'last_page' => $balances->lastPage(),
                    'per_page' => $balances->perPage(),
                    'total' => $balances->total(),
                ]
            ]
        ]);
    }

    /**
     * Mettre à jour la configuration de payout automatique d'un organisateur
     */
    public function updateAutoPayoutConfig(Request $request, Organizer $organizer): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'gateway' => 'required|in:airtelmoney,moovmoney',
            'auto_payout_enabled' => 'required|boolean',
            'auto_payout_threshold' => 'nullable|numeric|min:1000',
            'phone_number' => 'nullable|string|size:9',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $organizerBalance = OrganizerBalance::firstOrCreate([
                'organizer_id' => $organizer->id,
                'gateway' => $request->gateway,
            ], [
                'balance' => 0,
                'pending_balance' => 0,
            ]);

            $organizerBalance->update([
                'auto_payout_enabled' => $request->auto_payout_enabled,
                'auto_payout_threshold' => $request->auto_payout_threshold ?? 0,
                'phone_number' => $request->phone_number,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configuration de payout automatique mise à jour',
                'data' => ['balance' => $organizerBalance]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour config auto payout', [
                'organizer_id' => $organizer->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Obtenir le solde SHAP par opérateur
     */
    public function shapBalance(): JsonResponse
    {
        try {
            $result = $this->shapPayoutService->getBalance();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => ['balances' => $result['data']]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);

        } catch (\Exception $e) {
            Log::error('Erreur récupération solde SHAP admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la récupération du solde SHAP'
            ], 500);
        }
    }

    /**
     * Statistiques des payouts
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->startOfMonth());
            $dateTo = $request->get('date_to', now()->endOfMonth());

            $stats = [
                'total_payouts' => Payout::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'total_amount' => Payout::whereBetween('created_at', [$dateFrom, $dateTo])->sum('amount'),
                'successful_payouts' => Payout::whereBetween('created_at', [$dateFrom, $dateTo])->where('status', 'success')->count(),
                'failed_payouts' => Payout::whereBetween('created_at', [$dateFrom, $dateTo])->where('status', 'failed')->count(),
                'pending_payouts' => Payout::whereBetween('created_at', [$dateFrom, $dateTo])->whereIn('status', ['pending', 'processing'])->count(),
                'automatic_payouts' => Payout::whereBetween('created_at', [$dateFrom, $dateTo])->where('is_automatic', true)->count(),
                'manual_payouts' => Payout::whereBetween('created_at', [$dateFrom, $dateTo])->where('is_automatic', false)->count(),
            ];

            // Répartition par gateway
            $by_gateway = Payout::whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('gateway, COUNT(*) as count, SUM(amount) as total_amount')
                ->groupBy('gateway')
                ->get();

            $stats['by_gateway'] = $by_gateway;

            return response()->json([
                'success' => true,
                'data' => ['stats' => $stats]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération stats payouts', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la récupération des statistiques'
            ], 500);
        }
    }

    /**
     * Vérifier tous les payouts en cours
     */
    public function checkAllPending(): JsonResponse
    {
        try {
            $results = $this->payoutService->checkPendingPayouts();

            return response()->json([
                'success' => true,
                'message' => 'Vérification des payouts en cours terminée',
                'data' => ['results' => $results]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur vérification batch payouts admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la vérification'
            ], 500);
        }
    }

    /**
     * Liste des organisateurs
     */
    public function organizers(Request $request): JsonResponse
    {
        try {
            $organizers = Organizer::select('id', 'name', 'slug')
                ->where('status', 'active')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => ['organizers' => $organizers]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération organisateurs admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la récupération des organisateurs'
            ], 500);
        }
    }

    /**
     * Balances des organisateurs avec stats
     */
    public function balances(Request $request): JsonResponse
    {
        try {
            $query = OrganizerBalance::with(['organizer:id,name']);

            // Filtres
            if ($request->filled('gateway')) {
                $query->where('gateway', $request->gateway);
            }

            if ($request->filled('auto_payout_enabled')) {
                $query->where('auto_payout_enabled', $request->boolean('auto_payout_enabled'));
            }

            $balances = $query->orderBy('balance', 'desc')->get();

            // Statistiques
            $stats = [
                'total_organizers' => Organizer::where('status', 'active')->count(),
                'auto_payout_enabled' => OrganizerBalance::where('auto_payout_enabled', true)->count(),
                'total_balance' => OrganizerBalance::sum('balance'),
                'active_configs' => OrganizerBalance::where('auto_payout_enabled', true)
                    ->whereNotNull('payout_phone_number')
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'balances' => $balances,
                    'stats' => $stats
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération balances organisateurs', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la récupération des balances'
            ], 500);
        }
    }
}