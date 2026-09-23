<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SupervisionController;
use App\Models\PageView;
use App\Models\Role;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Supervision de la plateforme.
 *
 * Trois questions : est-ce que ça marche, combien de monde vient, qu'est-ce
 * qui tourne. Les écrans sont réservés aux super administrateurs, parce qu'ils
 * montrent l'état technique et les flux financiers en cours.
 */
class SupervisionTest extends TestCase
{
    use RefreshDatabase;

    private function user(bool $superAdmin): User
    {
        $type = UserType::firstOrCreate(['code' => 'admin'], ['name' => 'admin', 'label' => 'Admin']);

        $user = User::create([
            'name' => 'Agent', 'email' => 'agent-' . uniqid() . '@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        $user->user_type_id = $type->id;
        $user->save();

        $admin = Role::firstOrCreate(
            ['slug' => Role::ADMIN],
            ['name' => Role::ADMIN, 'description' => 'Administrateur']
        );
        $user->roles()->syncWithoutDetaching([$admin->id]);

        if ($superAdmin) {
            $super = Role::firstOrCreate(
                ['slug' => Role::SUPER_ADMIN],
                ['name' => 'Super Admin', 'description' => 'Accès complet au système', 'level' => 100]
            );
            $user->roles()->syncWithoutDetaching([$super->id]);
        }

        return $user->fresh('roles');
    }

    // ---------------------------------------------------------------
    //  Accès
    // ---------------------------------------------------------------

    public function test_an_ordinary_administrator_is_turned_away(): void
    {
        Sanctum::actingAs($this->user(superAdmin: false));

        $this->getJson('/api/v1/admin/supervision/health')
            ->assertStatus(403)
            ->assertJsonPath('message', 'Réservé aux super administrateurs.');
    }

    public function test_a_visitor_is_turned_away(): void
    {
        $this->getJson('/api/v1/admin/supervision/health')->assertStatus(401);
    }

    public function test_a_super_administrator_gets_in(): void
    {
        Sanctum::actingAs($this->user(superAdmin: true));

        $this->getJson('/api/v1/admin/supervision/health')->assertOk();
    }

    // ---------------------------------------------------------------
    //  Santé
    // ---------------------------------------------------------------

    public function test_the_health_page_reports_every_check(): void
    {
        Sanctum::actingAs($this->user(superAdmin: true));

        $data = $this->getJson('/api/v1/admin/supervision/health')->assertOk()->json('data');

        $keys = array_column($data['checks'], 'key');

        foreach (['database', 'cache', 'storage', 'queue', 'scheduler', 'payments'] as $expected) {
            $this->assertContains($expected, $keys);
        }

        $this->assertContains($data['status'], ['ok', 'warning', 'down']);
    }

    public function test_a_silent_scheduler_is_reported_as_down(): void
    {
        // Sans cron, les paiements en attente ne sont plus vérifiés et les
        // versements s'arrêtent : c'est une panne, pas un avertissement.
        Cache::put(SupervisionController::HEARTBEAT_KEY, now()->subHour()->toIso8601String(), 3600);
        Sanctum::actingAs($this->user(superAdmin: true));

        $checks = collect($this->getJson('/api/v1/admin/supervision/health')->json('data.checks'));
        $scheduler = $checks->firstWhere('key', 'scheduler');

        $this->assertSame('down', $scheduler['status']);
        $this->assertNotNull($scheduler['hint'], 'une panne doit dire quoi faire');
    }

    public function test_a_beating_scheduler_is_reported_as_healthy(): void
    {
        Cache::put(SupervisionController::HEARTBEAT_KEY, now()->toIso8601String(), 3600);
        Sanctum::actingAs($this->user(superAdmin: true));

        $checks = collect($this->getJson('/api/v1/admin/supervision/health')->json('data.checks'));

        $this->assertSame('ok', $checks->firstWhere('key', 'scheduler')['status']);
    }

    public function test_the_worst_check_decides_the_overall_state(): void
    {
        Cache::put(SupervisionController::HEARTBEAT_KEY, now()->subHour()->toIso8601String(), 3600);
        Sanctum::actingAs($this->user(superAdmin: true));

        $this->assertSame('down', $this->getJson('/api/v1/admin/supervision/health')->json('data.status'));
    }

    // ---------------------------------------------------------------
    //  Trafic
    // ---------------------------------------------------------------

    public function test_traffic_counts_visitors_and_views(): void
    {
        PageView::create(['path' => '/', 'visitor_hash' => str_repeat('a', 64), 'device' => 'mobile', 'referrer_source' => 'whatsapp']);
        PageView::create(['path' => '/events', 'visitor_hash' => str_repeat('a', 64), 'device' => 'mobile', 'referrer_source' => 'whatsapp']);
        PageView::create(['path' => '/', 'visitor_hash' => str_repeat('b', 64), 'device' => 'desktop', 'referrer_source' => 'direct']);

        Sanctum::actingAs($this->user(superAdmin: true));

        $data = $this->getJson('/api/v1/admin/supervision/traffic?days=7')->assertOk()->json('data');

        // Deux visiteurs distincts, trois pages vues.
        $this->assertSame(2, $data['totals']['visitors']);
        $this->assertSame(3, $data['totals']['views']);
        $this->assertSame(1.5, $data['totals']['views_per_visitor']);
        $this->assertSame(2, $data['totals']['today_visitors']);
    }

    public function test_the_daily_series_has_no_holes(): void
    {
        Sanctum::actingAs($this->user(superAdmin: true));

        $daily = $this->getJson('/api/v1/admin/supervision/traffic?days=30')->json('data.daily');

        // Une courbe trouée se lit mal et laisse croire à une panne de mesure.
        $this->assertCount(30, $daily);
        $this->assertSame(0, $daily[0]['visitors']);
        $this->assertSame(now()->toDateString(), end($daily)['date']);
    }

    public function test_traffic_breaks_down_by_page_source_and_device(): void
    {
        foreach (range(1, 3) as $i) {
            PageView::create(['path' => '/chill-expo', 'visitor_hash' => str_repeat((string) $i, 64), 'device' => 'mobile', 'referrer_source' => 'whatsapp']);
        }
        PageView::create(['path' => '/events', 'visitor_hash' => str_repeat('z', 64), 'device' => 'desktop', 'referrer_source' => 'google']);

        Sanctum::actingAs($this->user(superAdmin: true));

        $data = $this->getJson('/api/v1/admin/supervision/traffic')->json('data');

        $this->assertSame('/chill-expo', $data['top_pages'][0]['path']);
        $this->assertSame(3, $data['top_pages'][0]['views']);
        $this->assertSame('whatsapp', $data['referrers'][0]['source']);
        $this->assertSame('mobile', $data['devices'][0]['device']);
    }

    public function test_an_absurd_period_falls_back_to_a_sane_one(): void
    {
        Sanctum::actingAs($this->user(superAdmin: true));

        $this->assertSame(
            30,
            $this->getJson('/api/v1/admin/supervision/traffic?days=99999')->json('data.range.days')
        );
    }

    // ---------------------------------------------------------------
    //  Flux
    // ---------------------------------------------------------------

    public function test_flows_report_the_moving_parts(): void
    {
        Sanctum::actingAs($this->user(superAdmin: true));

        $data = $this->getJson('/api/v1/admin/supervision/flows?hours=72')->assertOk()->json('data');

        $this->assertSame(72, $data['window_hours']);

        foreach (['payments', 'orders', 'tickets', 'queue', 'scans'] as $section) {
            $this->assertArrayHasKey($section, $data);
        }

        $this->assertArrayHasKey('stuck', $data['payments']);
        $this->assertArrayHasKey('failed', $data['queue']);
    }
}
