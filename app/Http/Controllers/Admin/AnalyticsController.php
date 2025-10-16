<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Exports\SalesExport;
use App\Exports\EventsExport;
use App\Exports\FinancialExport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get dashboard KPIs
     */
    public function kpis(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $kpis = $this->analyticsService->getKPIs($days);

            return response()->json([
                'success' => true,
                'data' => $kpis
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération KPIs', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des KPIs'
            ], 500);
        }
    }

    /**
     * Get revenue chart data
     */
    public function revenueChart(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $chartData = $this->analyticsService->getRevenueChartData($days);

            return response()->json([
                'success' => true,
                'data' => $chartData
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur génération graphique revenus', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du graphique'
            ], 500);
        }
    }

    /**
     * Get sales by category chart
     */
    public function salesByCategory(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $chartData = $this->analyticsService->getSalesByCategory($days);

            return response()->json([
                'success' => true,
                'data' => $chartData
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur génération graphique catégories', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du graphique'
            ], 500);
        }
    }

    /**
     * Get top performing events
     */
    public function topEvents(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $events = $this->analyticsService->getTopEvents($limit);

            return response()->json([
                'success' => true,
                'data' => $events
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération top événements', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des événements'
            ], 500);
        }
    }

    /**
     * Get conversion funnel
     */
    public function conversionFunnel(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $funnelData = $this->analyticsService->getConversionFunnel($days);

            return response()->json([
                'success' => true,
                'data' => $funnelData
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur génération funnel', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du funnel'
            ], 500);
        }
    }

    /**
     * Get revenue predictions
     */
    public function predictions(Request $request): JsonResponse
    {
        try {
            $daysHistory = $request->get('days_history', 90);
            $daysFuture = $request->get('days_future', 30);

            $predictions = $this->analyticsService->predictRevenue($daysHistory, $daysFuture);

            return response()->json([
                'success' => true,
                'data' => $predictions
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur génération prédictions', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération des prédictions'
            ], 500);
        }
    }

    /**
     * Export sales data to Excel
     */
    public function exportSales(Request $request)
    {
        try {
            $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
            $endDate = $request->get('end_date', now()->toDateString());

            $fileName = 'ventes_' . $startDate . '_' . $endDate . '.xlsx';

            Log::info('Export ventes Excel', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'file_name' => $fileName
            ]);

            return Excel::download(
                new SalesExport($startDate, $endDate),
                $fileName
            );

        } catch (\Exception $e) {
            Log::error('Erreur export ventes Excel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export events data to Excel
     */
    public function exportEvents(Request $request)
    {
        try {
            $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
            $endDate = $request->get('end_date', now()->toDateString());

            $fileName = 'evenements_' . $startDate . '_' . $endDate . '.xlsx';

            Log::info('Export événements Excel', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'file_name' => $fileName
            ]);

            return Excel::download(
                new EventsExport($startDate, $endDate),
                $fileName
            );

        } catch (\Exception $e) {
            Log::error('Erreur export événements Excel', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export financial report to Excel
     */
    public function exportFinancial(Request $request)
    {
        try {
            $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
            $endDate = $request->get('end_date', now()->toDateString());

            $fileName = 'rapport_financier_' . $startDate . '_' . $endDate . '.xlsx';

            Log::info('Export rapport financier Excel', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'file_name' => $fileName
            ]);

            return Excel::download(
                new FinancialExport($startDate, $endDate),
                $fileName
            );

        } catch (\Exception $e) {
            Log::error('Erreur export rapport financier Excel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comprehensive analytics dashboard data
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);

            $data = [
                'kpis' => $this->analyticsService->getKPIs($days),
                'revenue_chart' => $this->analyticsService->getRevenueChartData($days),
                'sales_by_category' => $this->analyticsService->getSalesByCategory($days),
                'top_events' => $this->analyticsService->getTopEvents(5),
                'conversion_funnel' => $this->analyticsService->getConversionFunnel($days),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération dashboard analytics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du dashboard'
            ], 500);
        }
    }
}
