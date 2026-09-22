<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackfillPaidAtTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_backfill_fills_paid_orders_from_their_payment(): void
    {
        Schema::table('orders', fn ($t) => $t->dropColumn('paid_at'));

        $org = DB::table('organizers')->insertGetId(['name' => 'O', 'slug' => 'o', 'status' => 'active', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $withPayment = DB::table('orders')->insertGetId(['organizer_id' => $org, 'buyer_id' => 0, 'currency' => 'XAF', 'subtotal_amount' => 900, 'total_amount' => 1000, 'status' => 'paid', 'reference' => 'A', 'placed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $withoutPayment = DB::table('orders')->insertGetId(['organizer_id' => $org, 'buyer_id' => 0, 'currency' => 'XAF', 'subtotal_amount' => 900, 'total_amount' => 1000, 'status' => 'paid', 'reference' => 'B', 'placed_at' => now(), 'created_at' => now(), 'updated_at' => now()->subDay()]);
        $pending = DB::table('orders')->insertGetId(['organizer_id' => $org, 'buyer_id' => 0, 'currency' => 'XAF', 'subtotal_amount' => 900, 'total_amount' => 1000, 'status' => 'pending', 'reference' => 'C', 'placed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        DB::table('payments')->insert(['order_id' => $withPayment, 'provider' => 'moov', 'provider_txn_ref' => 'X', 'amount' => 1000, 'status' => 'success', 'paid_at' => '2026-09-01 10:00:00', 'created_at' => now(), 'updated_at' => now()]);

        (require database_path('migrations/2026_09_22_141500_add_paid_at_to_orders.php'))->up();

        $this->assertSame('2026-09-01 10:00:00', DB::table('orders')->find($withPayment)->paid_at);
        $this->assertNotNull(DB::table('orders')->find($withoutPayment)->paid_at, 'repli sur updated_at');
        $this->assertNull(DB::table('orders')->find($pending)->paid_at, 'une commande en attente reste vide');
    }
}
