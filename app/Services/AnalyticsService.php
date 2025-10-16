<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsService
{
    /**
     * Get revenue chart data for the last N days
     */
    public function getRevenueChartData(int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();

        $dailyRevenue = Payment::where('status', 'success')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, SUM(amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Remplir les jours manquants avec 0
        $data = [];
        $labels = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $labels[] = $currentDate->format('d/m');

            $dayRevenue = $dailyRevenue->firstWhere('date', $dateStr);
            $data[] = $dayRevenue ? (float) $dayRevenue->revenue : 0;

            $currentDate->addDay();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Revenus (XAF)',
                    'data' => $data,
                    'borderColor' => 'rgb(79, 70, 229)',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                    'tension' => 0.4
                ]
            ]
        ];
    }

    /**
     * Get sales by event category
     */
    public function getSalesByCategory(int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();

        $categoryData = DB::table('tickets')
            ->join('events', 'tickets.event_id', '=', 'events.id')
            ->join('categories', 'events.category_id', '=', 'categories.id')
            ->join('orders', 'tickets.order_id', '=', 'orders.id')
            ->where('orders.status', 'paid')
            ->whereBetween('tickets.created_at', [$startDate, now()])
            ->select('categories.name as category', DB::raw('COUNT(tickets.id) as count'))
            ->groupBy('categories.id', 'categories.name')
            ->get();

        return [
            'labels' => $categoryData->pluck('category')->toArray(),
            'datasets' => [
                [
                    'label' => 'Billets vendus',
                    'data' => $categoryData->pluck('count')->toArray(),
                    'backgroundColor' => [
                        'rgba(239, 68, 68, 0.8)',   // red
                        'rgba(249, 115, 22, 0.8)',  // orange
                        'rgba(234, 179, 8, 0.8)',   // yellow
                        'rgba(34, 197, 94, 0.8)',   // green
                        'rgba(59, 130, 246, 0.8)',  // blue
                        'rgba(139, 92, 246, 0.8)',  // purple
                        'rgba(236, 72, 153, 0.8)',  // pink
                    ]
                ]
            ]
        ];
    }

    /**
     * Get top performing events
     */
    public function getTopEvents(int $limit = 10): array
    {
        return Event::withCount(['tickets' => function($query) {
                $query->whereIn('status', ['issued', 'used']);
            }])
            ->with(['organizer'])
            ->where('status', 'published')
            ->orderBy('tickets_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($event) {
                $revenue = $event->tickets()
                    ->join('orders', 'tickets.order_id', '=', 'orders.id')
                    ->where('orders.status', 'paid')
                    ->sum('orders.total_amount');

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'organizer' => $event->organizer ? $event->organizer->name : 'N/A',
                    'tickets_sold' => $event->tickets_count,
                    'revenue' => $revenue,
                    'image' => $event->image
                ];
            })
            ->toArray();
    }

    /**
     * Get conversion funnel data
     */
    public function getConversionFunnel(int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();

        // Simulated data (en production, tracker les vues d'événements)
        $eventViews = Event::where('status', 'published')->count() * 150; // Simulé
        $ordersStarted = Order::whereBetween('created_at', [$startDate, now()])->count();
        $ordersPaid = Order::where('status', 'paid')
            ->whereBetween('created_at', [$startDate, now()])
            ->count();

        return [
            'labels' => ['Vues événements', 'Commandes créées', 'Paiements réussis'],
            'datasets' => [
                [
                    'label' => 'Utilisateurs',
                    'data' => [$eventViews, $ordersStarted, $ordersPaid],
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(139, 92, 246, 0.8)'
                    ]
                ]
            ],
            'conversion_rates' => [
                'view_to_order' => $eventViews > 0 ? round(($ordersStarted / $eventViews) * 100, 2) : 0,
                'order_to_paid' => $ordersStarted > 0 ? round(($ordersPaid / $ordersStarted) * 100, 2) : 0,
                'overall' => $eventViews > 0 ? round(($ordersPaid / $eventViews) * 100, 2) : 0
            ]
        ];
    }

    /**
     * Predict future revenue using linear regression
     */
    public function predictRevenue(int $daysHistory = 90, int $daysFuture = 30): array
    {
        $startDate = now()->subDays($daysHistory)->startOfDay();

        // Récupérer les données historiques
        $historicalData = Payment::where('status', 'success')
            ->whereBetween('created_at', [$startDate, now()])
            ->selectRaw('DATE(created_at) as date, SUM(amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        if ($historicalData->count() < 7) {
            return [
                'message' => 'Pas assez de données historiques pour faire une prédiction',
                'predictions' => []
            ];
        }

        // Préparer les données pour la régression
        $x = [];
        $y = [];

        foreach ($historicalData as $index => $data) {
            $x[] = $index;
            $y[] = (float) $data->revenue;
        }

        // Calculer la régression linéaire: y = a + bx
        $regression = $this->linearRegression($x, $y);

        // Générer les prédictions
        $predictions = [];
        $labels = [];
        $lastIndex = count($x);

        for ($i = 0; $i < $daysFuture; $i++) {
            $futureDate = now()->addDays($i + 1);
            $predictedValue = $regression['a'] + ($regression['b'] * ($lastIndex + $i));

            // Ne pas prédire de valeurs négatives
            $predictedValue = max(0, $predictedValue);

            $predictions[] = round($predictedValue, 2);
            $labels[] = $futureDate->format('d/m');
        }

        // Calculer l'intervalle de confiance (simplifié)
        $stdDev = $this->standardDeviation($y);
        $confidenceLower = array_map(fn($v) => max(0, $v - $stdDev), $predictions);
        $confidenceUpper = array_map(fn($v) => $v + $stdDev, $predictions);

        return [
            'labels' => $labels,
            'predictions' => $predictions,
            'confidence_lower' => $confidenceLower,
            'confidence_upper' => $confidenceUpper,
            'trend' => $regression['b'] > 0 ? 'ascending' : 'descending',
            'growth_rate' => round($regression['b'], 2),
            'r_squared' => round($regression['r_squared'], 4)
        ];
    }

    /**
     * Get key performance indicators
     */
    public function getKPIs(int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        $previousPeriodStart = now()->subDays($days * 2)->startOfDay();
        $previousPeriodEnd = $startDate->copy()->subDay()->endOfDay();

        // Current period
        $currentRevenue = Payment::where('status', 'success')
            ->whereBetween('created_at', [$startDate, now()])
            ->sum('amount');

        $currentOrders = Order::where('status', 'paid')
            ->whereBetween('created_at', [$startDate, now()])
            ->count();

        $currentTickets = Ticket::whereIn('status', ['issued', 'used'])
            ->whereBetween('created_at', [$startDate, now()])
            ->count();

        $currentUsers = User::whereBetween('created_at', [$startDate, now()])->count();

        // Previous period
        $previousRevenue = Payment::where('status', 'success')
            ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->sum('amount');

        $previousOrders = Order::where('status', 'paid')
            ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->count();

        $previousTickets = Ticket::whereIn('status', ['issued', 'used'])
            ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->count();

        $previousUsers = User::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->count();

        return [
            'revenue' => [
                'current' => $currentRevenue,
                'previous' => $previousRevenue,
                'change_percent' => $this->calculateChangePercent($currentRevenue, $previousRevenue),
                'trend' => $currentRevenue >= $previousRevenue ? 'up' : 'down'
            ],
            'orders' => [
                'current' => $currentOrders,
                'previous' => $previousOrders,
                'change_percent' => $this->calculateChangePercent($currentOrders, $previousOrders),
                'trend' => $currentOrders >= $previousOrders ? 'up' : 'down'
            ],
            'tickets' => [
                'current' => $currentTickets,
                'previous' => $previousTickets,
                'change_percent' => $this->calculateChangePercent($currentTickets, $previousTickets),
                'trend' => $currentTickets >= $previousTickets ? 'up' : 'down'
            ],
            'users' => [
                'current' => $currentUsers,
                'previous' => $previousUsers,
                'change_percent' => $this->calculateChangePercent($currentUsers, $previousUsers),
                'trend' => $currentUsers >= $previousUsers ? 'up' : 'down'
            ],
            'avg_order_value' => [
                'current' => $currentOrders > 0 ? round($currentRevenue / $currentOrders, 2) : 0,
                'previous' => $previousOrders > 0 ? round($previousRevenue / $previousOrders, 2) : 0
            ]
        ];
    }

    /**
     * Calculate linear regression
     */
    private function linearRegression(array $x, array $y): array
    {
        $n = count($x);

        if ($n === 0) {
            return ['a' => 0, 'b' => 0, 'r_squared' => 0];
        }

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $y[$i] * $y[$i];
        }

        $denominator = ($n * $sumX2 - $sumX * $sumX);

        if ($denominator == 0) {
            return ['a' => 0, 'b' => 0, 'r_squared' => 0];
        }

        $b = ($n * $sumXY - $sumX * $sumY) / $denominator;
        $a = ($sumY - $b * $sumX) / $n;

        // Calculate R²
        $yMean = $sumY / $n;
        $ssTotal = 0;
        $ssResidual = 0;

        for ($i = 0; $i < $n; $i++) {
            $yPred = $a + $b * $x[$i];
            $ssTotal += pow($y[$i] - $yMean, 2);
            $ssResidual += pow($y[$i] - $yPred, 2);
        }

        $rSquared = $ssTotal > 0 ? 1 - ($ssResidual / $ssTotal) : 0;

        return [
            'a' => $a,
            'b' => $b,
            'r_squared' => $rSquared
        ];
    }

    /**
     * Calculate standard deviation
     */
    private function standardDeviation(array $data): float
    {
        $n = count($data);

        if ($n === 0) {
            return 0;
        }

        $mean = array_sum($data) / $n;
        $variance = 0;

        foreach ($data as $value) {
            $variance += pow($value - $mean, 2);
        }

        return sqrt($variance / $n);
    }

    /**
     * Calculate percentage change
     */
    private function calculateChangePercent($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}
