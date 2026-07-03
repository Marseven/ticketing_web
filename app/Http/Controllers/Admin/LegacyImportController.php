<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class LegacyImportController extends Controller
{
    /** Tables métier dont on affiche le compteur. */
    private array $countedTables = [
        'events', 'event_schedules', 'ticket_types', 'orders', 'payments',
        'tickets', 'checkins', 'venues', 'organizers', 'users',
    ];

    /**
     * État : connexion legacy, compteurs actuels, compteurs déjà importés,
     * périmètre disponible côté legacy.
     */
    public function status(): JsonResponse
    {
        $current = [];
        foreach ($this->countedTables as $t) {
            $current[$t] = DB::table($t)->count();
        }

        $imported = [
            'events' => DB::table('events')->whereNotNull('legacy_id')->count(),
            'tickets' => DB::table('tickets')->whereNotNull('legacy_id')->count(),
            'orders' => DB::table('orders')->whereNotNull('legacy_id')->count(),
        ];

        $legacy = ['connected' => false];
        try {
            DB::connection('legacy')->getPdo();
            $legacy = [
                'connected' => true,
                'events' => DB::connection('legacy')->table('leweb_event')->where('sup', 0)->count(),
                'owners' => DB::connection('legacy')->table('leweb_owner')->count(),
                'users' => DB::connection('legacy')->table('leweb_users')->count(),
                'tickets' => DB::connection('legacy')->table('leweb_ticket')->count(),
                'pay_paid' => DB::connection('legacy')->table('leweb_pay')->where('statut', 'PAID')->count(),
            ];
        } catch (\Throwable $e) {
            $legacy['error'] = $e->getMessage();
        }

        return response()->json([
            'success' => true,
            'data' => ['current' => $current, 'imported' => $imported, 'legacy' => $legacy],
        ]);
    }

    /**
     * Aperçu (dry-run) : exécute l'import en simulation et renvoie le journal.
     */
    public function preview(Request $request): JsonResponse
    {
        $params = ['--dry-run' => true];
        if ($request->boolean('all')) {
            $params['--all'] = true;
        }

        return $this->runArtisan($params, false);
    }

    /**
     * Exécution réelle. Requiert une confirmation explicite.
     */
    public function run(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'confirm' => 'required|string',
            'fresh' => 'boolean',
            'all' => 'boolean',
        ]);
        if ($validator->fails() || strtoupper(trim($request->input('confirm'))) !== 'REMPLACER') {
            return response()->json([
                'success' => false,
                'message' => 'Confirmation requise : tapez REMPLACER pour lancer l\'import.',
            ], 422);
        }

        // Connexion legacy obligatoire
        try {
            DB::connection('legacy')->getPdo();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connexion à la base legacy impossible. Vérifiez LEGACY_DB_* dans .env.',
            ], 400);
        }

        $params = [];
        if ($request->boolean('fresh')) {
            $params['--fresh'] = true;
        }
        if ($request->boolean('all')) {
            $params['--all'] = true;
        }

        return $this->runArtisan($params, true);
    }

    private function runArtisan(array $params, bool $withNewCounts): JsonResponse
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        try {
            Artisan::call('legacy:import', $params);
            $output = Artisan::output();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Échec de l\'import : ' . $e->getMessage(),
            ], 500);
        }

        $data = ['output' => $output];
        if ($withNewCounts) {
            $counts = [];
            foreach ($this->countedTables as $t) {
                $counts[$t] = DB::table($t)->count();
            }
            $data['counts'] = $counts;
        }

        return response()->json(['success' => true, 'data' => $data]);
    }
}
