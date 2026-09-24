<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PaymentGatewayUnavailable;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PageView;
use App\Models\Payment;
use App\Services\EbillingBillState;
use App\Models\Ticket;
use App\Services\EBillingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Supervision de la plateforme.
 *
 * Trois questions, trois réponses : est-ce que ça marche, combien de monde
 * vient, et qu'est-ce qui tourne en ce moment.
 *
 * Les contrôles de santé ne doivent JAMAIS faire tomber la page : un contrôle
 * qui échoue est un résultat, pas une erreur. Chacun est donc isolé, et une
 * exception se traduit par un état « en panne » assorti de son motif.
 */
class SupervisionController extends Controller
{
    /** Au-delà, la file d'attente mérite qu'on s'en inquiète. */
    private const QUEUE_WARNING = 25;

    /** Un paiement encore « en cours » après ce délai est probablement perdu. */
    private const STUCK_PAYMENT_MINUTES = 30;

    /** Sans battement du planificateur depuis ce délai, le cron ne tourne plus. */
    private const SCHEDULER_STALE_MINUTES = 10;

    /** Clé du battement écrit par le planificateur (routes/console.php). */
    public const HEARTBEAT_KEY = 'supervision.scheduler.heartbeat';

    // -----------------------------------------------------------------
    //  État de santé
    // -----------------------------------------------------------------

    public function health(): JsonResponse
    {
        $checks = [
            $this->checkDatabase(),
            $this->checkCache(),
            $this->checkStorage(),
            $this->checkQueue(),
            $this->checkScheduler(),
            $this->checkPaymentGateway(),
            $this->checkStuckPayments(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'checked_at' => now()->format('c'),
                'status' => $this->worst($checks),
                'checks' => $checks,
            ],
        ]);
    }

    /** L'état le plus grave l'emporte : un seul point rouge rend la page rouge. */
    private function worst(array $checks): string
    {
        $statuses = array_column($checks, 'status');

        if (in_array('down', $statuses, true)) {
            return 'down';
        }

        return in_array('warning', $statuses, true) ? 'warning' : 'ok';
    }

    /** @return array{key:string,label:string,status:string,detail:string,hint:?string} */
    private function check(string $key, string $label, callable $probe): array
    {
        try {
            [$status, $detail, $hint] = $probe();
        } catch (\Throwable $e) {
            // Un contrôle qui casse est une panne constatée, pas une erreur
            // de la page : on la montre au lieu de la laisser remonter.
            return [
                'key' => $key, 'label' => $label, 'status' => 'down',
                'detail' => $e->getMessage(), 'hint' => null,
            ];
        }

        return compact('key', 'label', 'status', 'detail', 'hint');
    }

    private function checkDatabase(): array
    {
        return $this->check('database', 'Base de données', function () {
            $start = microtime(true);
            DB::select('select 1');
            $ms = (int) round((microtime(true) - $start) * 1000);

            return $ms > 500
                ? ['warning', "Répond en {$ms} ms", 'La base est lente : vérifier la charge du serveur.']
                : ['ok', "Répond en {$ms} ms", null];
        });
    }

    private function checkCache(): array
    {
        return $this->check('cache', 'Cache', function () {
            $key = 'supervision.probe';
            $value = (string) now()->timestamp;

            Cache::put($key, $value, 10);
            $read = Cache::get($key);
            Cache::forget($key);

            return $read === $value
                ? ['ok', 'Lecture et écriture correctes', null]
                : ['down', 'La valeur écrite ne se relit pas', 'Vérifier le pilote de cache.'];
        });
    }

    private function checkStorage(): array
    {
        return $this->check('storage', 'Stockage des fichiers', function () {
            $disk = Storage::disk('public');
            $path = 'supervision-probe.txt';

            $disk->put($path, 'ok');
            $ok = $disk->get($path) === 'ok';
            $disk->delete($path);

            $free = @disk_free_space(base_path());
            $total = @disk_total_space(base_path());
            $detail = 'Écriture possible';

            if ($free && $total) {
                $percent = (int) round($free / $total * 100);
                $detail .= ' · ' . $this->humanBytes($free) . " libres ({$percent} %)";

                if ($percent < 10) {
                    return ['warning', $detail, 'Moins de 10 % d\'espace disque : faire de la place.'];
                }
            }

            return $ok
                ? ['ok', $detail, null]
                : ['down', 'Écriture impossible', 'Vérifier les droits sur storage/app/public.'];
        });
    }

    private function checkQueue(): array
    {
        return $this->check('queue', "File d'attente", function () {
            if (! Schema::hasTable('jobs')) {
                return ['ok', 'Aucune file en base', null];
            }

            $pending = DB::table('jobs')->count();
            $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

            $detail = "{$pending} en attente · {$failed} en échec";

            if ($failed > 0) {
                return ['warning', $detail, 'Inspecter avec « php artisan queue:failed ».'];
            }

            if ($pending > self::QUEUE_WARNING) {
                return ['warning', $detail, 'Vérifier que le cron « queue:work » tourne.'];
            }

            return ['ok', $detail, null];
        });
    }

    private function checkScheduler(): array
    {
        return $this->check('scheduler', 'Tâches planifiées', function () {
            $beat = Cache::get(self::HEARTBEAT_KEY);

            if (! $beat) {
                return [
                    'warning',
                    'Aucun battement enregistré',
                    'Le cron « schedule:run » doit tourner chaque minute.',
                ];
            }

            $last = CarbonImmutable::parse($beat);
            $minutes = (int) $last->diffInMinutes(now());
            $detail = 'Dernier passage ' . $last->locale('fr')->isoFormat('D MMM [à] HH:mm');

            return $minutes > self::SCHEDULER_STALE_MINUTES
                ? ['down', $detail . " (il y a {$minutes} min)", 'Le cron ne tourne plus : paiements et versements sont à l\'arrêt.']
                : ['ok', $detail, null];
        });
    }

    private function checkPaymentGateway(): array
    {
        return $this->check('payments', 'Passerelle de paiement', function () {
            try {
                app(EBillingService::class);
            } catch (PaymentGatewayUnavailable $e) {
                // Le motif technique reste ici, entre super administrateurs ;
                // l'acheteur, lui, n'a jamais vu que le message standard.
                return ['down', $e->getMessage(), 'Compléter le .env puis « php artisan optimize:clear && php artisan optimize ».'];
            }

            return ['ok', 'Identifiants présents', 'Vérifier qu\'ils sont acceptés : « php artisan payments:doctor --live ».'];
        });
    }

    private function checkStuckPayments(): array
    {
        return $this->check('stuck_payments', 'Paiements en souffrance', function () {
            // Deux populations à ne pas confondre. Un paiement dont la
            // commande attend encore se rattrape tout seul au prochain
            // passage. Un paiement dont la commande a été ANNULÉE, non : la
            // vérification périodique l'ignorait, et il pouvait rester là,
            // réglé ou non, sans que personne ne pose la question.
            $base = fn () => Payment::where('status', 'initiated')
                ->where('created_at', '<=', now()->subMinutes(self::STUCK_PAYMENT_MINUTES))
                ->where('created_at', '>=', now()->subDays(2));

            $waiting = (clone $base())
                ->whereHas('order', fn ($q) => $q->where('status', 'pending'))
                ->count();

            $closed = (clone $base())
                ->whereHas('order', fn ($q) => $q->where('status', '!=', 'pending'));

            // Réglée sur une commande close : de l'argent encaissé sans billet
            // en face. C'est la seule situation qui réclame un humain.
            $paidOnClosed = (clone $closed)
                ->whereIn('ebilling_state', EbillingBillState::PAID)
                ->count();

            if ($paidOnClosed > 0) {
                return ['error', "{$paidOnClosed} paiement(s) réglé(s) sur une commande annulée",
                    'Client débité sans billet : trancher entre émission et remboursement.'];
            }

            // Jamais interrogée : on ne sait rien, ce qui n'est pas la même
            // chose que « rien à signaler ».
            $unchecked = (clone $closed)->whereNull('ebilling_state')->count();

            if ($unchecked > 0) {
                return ['warning', "{$unchecked} paiement(s) sur commande annulée, état inconnu",
                    'Demander leur état à e-billing : « php artisan payments:check-pending --include-closed --dry-run ».'];
            }

            return $waiting === 0
                ? ['ok', 'Aucun paiement bloqué', null]
                : ['warning', "{$waiting} paiement(s) sans réponse depuis plus de " . self::STUCK_PAYMENT_MINUTES . ' min',
                   'Relancer « php artisan payments:check-pending ».'];
        });
    }

    // -----------------------------------------------------------------
    //  Trafic
    // -----------------------------------------------------------------

    public function traffic(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;

        $from = now()->startOfDay()->subDays($days - 1);

        $rows = PageView::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('date(created_at) as day')
            ->selectRaw('count(*) as views')
            ->selectRaw('count(distinct visitor_hash) as visitors')
            ->groupBy('day')
            ->pluck('views', 'day')
            ->all();

        $visitorsByDay = PageView::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('date(created_at) as day')
            ->selectRaw('count(distinct visitor_hash) as visitors')
            ->groupBy('day')
            ->pluck('visitors', 'day')
            ->all();

        // La série couvre chaque jour, même vide : une courbe trouée se lit mal
        // et laisse croire à une panne de mesure.
        $daily = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i)->toDateString();
            $daily[] = [
                'date' => $date,
                'visitors' => (int) ($visitorsByDay[$date] ?? 0),
                'views' => (int) ($rows[$date] ?? 0),
            ];
        }

        $views = array_sum(array_column($daily, 'views'));
        $visitors = (int) PageView::where('created_at', '>=', $from)->distinct('visitor_hash')->count('visitor_hash');
        $today = now()->toDateString();

        return response()->json([
            'success' => true,
            'data' => [
                'range' => ['from' => $from->toDateString(), 'to' => now()->toDateString(), 'days' => $days],
                'totals' => [
                    'visitors' => $visitors,
                    'views' => $views,
                    'views_per_visitor' => $visitors > 0 ? round($views / $visitors, 1) : 0,
                    'today_visitors' => (int) ($visitorsByDay[$today] ?? 0),
                    'today_views' => (int) ($rows[$today] ?? 0),
                ],
                'daily' => $daily,
                'top_pages' => $this->breakdown($from, 'path', 'path', 12),
                'referrers' => $this->breakdown($from, 'referrer_source', 'source', 8),
                'devices' => $this->breakdown($from, 'device', 'device', 4),
            ],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function breakdown(\Illuminate\Support\Carbon $from, string $column, string $key, int $limit): array
    {
        return PageView::query()
            ->where('created_at', '>=', $from)
            ->whereNotNull($column)
            ->selectRaw("{$column} as bucket, count(*) as views")
            ->groupBy('bucket')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [$key => $row->bucket, 'views' => (int) $row->views])
            ->all();
    }

    // -----------------------------------------------------------------
    //  Flux
    // -----------------------------------------------------------------

    public function flows(Request $request): JsonResponse
    {
        $hours = (int) $request->query('hours', 24);
        $hours = in_array($hours, [24, 72, 168], true) ? $hours : 24;

        $since = now()->subHours($hours);

        return response()->json([
            'success' => true,
            'data' => [
                'window_hours' => $hours,
                'payments' => [
                    'initiated' => Payment::where('created_at', '>=', $since)->count(),
                    'success' => Payment::where('created_at', '>=', $since)->where('status', 'success')->count(),
                    'failed' => Payment::where('created_at', '>=', $since)->where('status', 'failed')->count(),
                    'stuck' => Payment::where('status', 'initiated')
                        ->where('created_at', '<=', now()->subMinutes(self::STUCK_PAYMENT_MINUTES))
                        ->where('created_at', '>=', $since)
                        ->count(),
                ],
                'orders' => [
                    'pending' => Order::where('created_at', '>=', $since)->where('status', 'pending')->count(),
                    'paid' => Order::where('created_at', '>=', $since)->where('status', 'paid')->count(),
                    'cancelled' => Order::where('created_at', '>=', $since)->where('status', 'cancelled')->count(),
                ],
                'tickets' => [
                    'issued' => Ticket::where('created_at', '>=', $since)->count(),
                    'used' => Ticket::where('used_at', '>=', $since)->count(),
                ],
                'queue' => $this->queueFlow(),
                'scans' => $this->scanFlow($since),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function queueFlow(): array
    {
        if (! Schema::hasTable('jobs')) {
            return ['pending' => 0, 'failed' => 0, 'oldest_pending_seconds' => null];
        }

        $oldest = DB::table('jobs')->min('created_at');

        return [
            'pending' => DB::table('jobs')->count(),
            'failed' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
            // `jobs.created_at` est un entier Unix, pas une date.
            'oldest_pending_seconds' => $oldest ? max(0, now()->timestamp - (int) $oldest) : null,
        ];
    }

    /** @return array<string, int> */
    private function scanFlow(\Illuminate\Support\Carbon $since): array
    {
        if (! Schema::hasTable('checkins')) {
            return ['valid' => 0, 'duplicate' => 0, 'invalid' => 0];
        }

        $counts = DB::table('checkins')
            ->where('scanned_at', '>=', $since)
            ->selectRaw('result, count(*) as total')
            ->groupBy('result')
            ->pluck('total', 'result')
            ->all();

        return [
            'valid' => (int) ($counts['valid'] ?? 0),
            'duplicate' => (int) ($counts['duplicate'] ?? 0),
            'invalid' => (int) ($counts['invalid'] ?? 0),
        ];
    }

    private function humanBytes(float $bytes): string
    {
        foreach (['o', 'Ko', 'Mo', 'Go', 'To'] as $unit) {
            if ($bytes < 1024 || $unit === 'To') {
                return round($bytes, $unit === 'o' ? 0 : 1) . ' ' . $unit;
            }

            $bytes /= 1024;
        }

        return (string) $bytes;
    }
}
