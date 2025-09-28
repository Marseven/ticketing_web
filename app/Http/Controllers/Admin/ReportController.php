<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Event;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\Category;
use App\Models\Venue;
use App\Models\Organizer;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Liste des rapports et statistiques globales
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
            $endDate = $request->get('end_date', now()->toDateString());

            // Statistiques globales
            $stats = $this->getGlobalStats($startDate, $endDate);

            // Historique des rapports depuis la base de données
            $reports = $this->getReportsHistory();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'reports' => $reports
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération rapports', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du chargement des rapports'
            ], 500);
        }
    }

    /**
     * Générer un nouveau rapport
     */
    public function generate(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'type' => 'required|string|in:financial,events,users,performance',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $type = $request->type;
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            Log::info('Starting report generation', [
                'type' => $type,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ]);

            // Générer les données du rapport selon le type
            $reportData = $this->generateReportData($type, $startDate, $endDate);
            Log::info('Report data generated successfully', ['data_keys' => array_keys($reportData)]);

            // Simuler la génération de fichier
            $fileName = $this->generateFileName($type, $startDate, $endDate);
            $fileSize = $this->calculateFileSize($reportData);
            Log::info('File details generated', ['file_name' => $fileName, 'file_size' => $fileSize]);

            // Créer l'entrée du rapport en base
            Log::info('Creating report record in database');
            $report = Report::create([
                'type' => $type,
                'period' => $this->formatPeriod($startDate, $endDate),
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'file_name' => $fileName,
                'file_size' => $this->parseFileSize($fileSize),
                'format' => 'PDF',
                'status' => 'ready',
                'data' => $reportData,
            ]);
            Log::info('Report record created successfully', ['report_id' => $report->id]);

            // Préparer la réponse
            $reportResponse = [
                'id' => $report->id,
                'type' => $report->type,
                'period' => $report->period,
                'created_at' => $report->created_at->toISOString(),
                'file_size' => $report->file_size_human,
                'format' => $report->format,
                'status' => $report->status,
                'download_url' => $report->download_url
            ];

            return response()->json([
                'success' => true,
                'message' => 'Rapport généré avec succès',
                'data' => ['report' => $reportResponse]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur génération rapport', [
                'type' => $request->type ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la génération du rapport: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger un rapport
     */
    public function download(Request $request, $reportId): JsonResponse
    {
        try {
            // Simuler le téléchargement pour maintenant
            return response()->json([
                'success' => true,
                'message' => 'Rapport téléchargé avec succès',
                'download_url' => '/storage/reports/rapport-' . $reportId . '.pdf'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur téléchargement rapport', [
                'report_id' => $reportId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du téléchargement'
            ], 500);
        }
    }

    /**
     * Supprimer un rapport
     */
    public function destroy($reportId): JsonResponse
    {
        try {
            $report = Report::find($reportId);
            
            if (!$report) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rapport introuvable'
                ], 404);
            }

            // Supprimer le fichier physique si il existe
            if ($report->file_path && Storage::exists($report->file_path)) {
                Storage::delete($report->file_path);
            }

            // Supprimer l'entrée en base
            $report->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rapport supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur suppression rapport', [
                'report_id' => $reportId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la suppression'
            ], 500);
        }
    }

    /**
     * Vider l'historique des rapports
     */
    public function clear(): JsonResponse
    {
        try {
            // Récupérer tous les rapports
            $reports = Report::all();

            // Supprimer les fichiers physiques
            foreach ($reports as $report) {
                if ($report->file_path && Storage::exists($report->file_path)) {
                    Storage::delete($report->file_path);
                }
            }

            // Vider la table
            Report::truncate();

            return response()->json([
                'success' => true,
                'message' => 'Historique vidé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur vidage historique rapports', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du vidage'
            ], 500);
        }
    }

    /**
     * Récupérer les statistiques globales
     */
    private function getGlobalStats(string $startDate, string $endDate): array
    {
        try {
            // Revenus totaux (paiements réussis)
            $totalRevenue = Payment::where('status', 'success')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount');

            // Tickets vendus
            $totalTickets = Ticket::whereIn('status', ['issued', 'used'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // Événements actifs
            $activeEvents = Event::where('status', 'published')
                ->where('is_active', true)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // Utilisateurs actifs (connectés récemment)
            $activeUsers = User::where('status', 'active')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            return [
                'total_revenue' => $totalRevenue,
                'total_tickets' => $totalTickets,
                'active_events' => $activeEvents,
                'active_users' => $activeUsers
            ];

        } catch (\Exception $e) {
            Log::error('Erreur calcul statistiques', ['error' => $e->getMessage()]);
            
            return [
                'total_revenue' => 0,
                'total_tickets' => 0,
                'active_events' => 0,
                'active_users' => 0
            ];
        }
    }

    /**
     * Récupérer l'historique des rapports
     */
    private function getReportsHistory(): array
    {
        return Report::orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'type' => $report->type,
                    'period' => $report->period,
                    'created_at' => $report->created_at->toISOString(),
                    'file_size' => $report->file_size_human,
                    'format' => $report->format,
                    'status' => $report->status,
                    'download_url' => $report->download_url
                ];
            })
            ->toArray();
    }

    /**
     * Générer les données du rapport selon le type
     */
    private function generateReportData(string $type, Carbon $startDate, Carbon $endDate): array
    {
        switch ($type) {
            case 'financial':
                return $this->generateFinancialReport($startDate, $endDate);
            case 'events':
                return $this->generateEventsReport($startDate, $endDate);
            case 'users':
                return $this->generateUsersReport($startDate, $endDate);
            case 'performance':
                return $this->generatePerformanceReport($startDate, $endDate);
            default:
                return [];
        }
    }

    /**
     * Générer rapport financier
     */
    private function generateFinancialReport(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'revenus' => Payment::where('status', 'success')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
            'commissions' => Payment::where('status', 'success')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('commission_amount'),
            'nb_transactions' => Payment::whereBetween('created_at', [$startDate, $endDate])->count()
        ];
    }

    /**
     * Générer rapport événements
     */
    private function generateEventsReport(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'nb_events' => Event::whereBetween('created_at', [$startDate, $endDate])->count(),
            'events_published' => Event::where('status', 'published')
                ->whereBetween('created_at', [$startDate, $endDate])->count(),
            'tickets_sold' => Ticket::whereIn('status', ['issued', 'used'])
                ->whereBetween('created_at', [$startDate, $endDate])->count()
        ];
    }

    /**
     * Générer rapport utilisateurs
     */
    private function generateUsersReport(Carbon $startDate, Carbon $endDate): array
    {
        try {
            $newUsers = User::whereBetween('created_at', [$startDate, $endDate])->count();
            $activeUsers = User::where('status', 'active')->count();
            
            // Try the organizers query with error handling
            try {
                $organizers = User::whereHas('roles', function($q) {
                    $q->where('slug', 'organizer');
                })->count();
            } catch (\Exception $e) {
                Log::error('Error counting organizers', ['error' => $e->getMessage()]);
                // Fallback: try to count using role_user table directly
                $organizers = \DB::table('role_user')
                    ->join('roles', 'role_user.role_id', '=', 'roles.id')
                    ->where('roles.slug', 'organizer')
                    ->distinct('role_user.user_id')
                    ->count();
            }
            
            return [
                'new_users' => $newUsers,
                'active_users' => $activeUsers,
                'organizers' => $organizers
            ];
        } catch (\Exception $e) {
            Log::error('Error generating users report', ['error' => $e->getMessage()]);
            return [
                'new_users' => 0,
                'active_users' => 0,
                'organizers' => 0
            ];
        }
    }

    /**
     * Générer rapport performance
     */
    private function generatePerformanceReport(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'conversion_rate' => 85.6, // Simulé
            'avg_order_value' => Payment::where('status', 'success')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->avg('amount') ?? 0,
            'bounce_rate' => 23.4 // Simulé
        ];
    }

    /**
     * Générer nom de fichier
     */
    private function generateFileName(string $type, Carbon $startDate, Carbon $endDate): string
    {
        $dateStr = $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d');
        return "rapport_{$type}_{$dateStr}.pdf";
    }

    /**
     * Calculer taille de fichier simulée
     */
    private function calculateFileSize(array $data): string
    {
        $size = rand(500, 3000); // KB
        if ($size < 1024) {
            return $size . ' KB';
        }
        return round($size / 1024, 1) . ' MB';
    }

    /**
     * Parser la taille de fichier en bytes
     */
    private function parseFileSize(string $sizeStr): int
    {
        preg_match('/^(\d+(?:\.\d+)?)\s*(KB|MB|GB)?$/i', trim($sizeStr), $matches);
        
        $number = floatval($matches[1] ?? 0);
        $unit = strtoupper($matches[2] ?? '');
        
        switch ($unit) {
            case 'GB':
                return intval($number * 1024 * 1024 * 1024);
            case 'MB':
                return intval($number * 1024 * 1024);
            case 'KB':
                return intval($number * 1024);
            default:
                return intval($number);
        }
    }

    /**
     * Formater la période
     */
    private function formatPeriod(Carbon $startDate, Carbon $endDate): string
    {
        if ($startDate->isSameDay($endDate)) {
            return $startDate->format('d/m/Y');
        }
        return $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
    }
}