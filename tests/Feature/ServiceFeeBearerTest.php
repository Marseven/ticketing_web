<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Frais de service (e-billing) supportés PAR ÉVÉNEMENT :
 * - service_fee_bearer = 'customer' : les frais (2,5 %) sont AJOUTÉS au total
 *   payé par le client (ligne « Frais de service »).
 * - service_fee_bearer = 'platform' : la plateforme absorbe les frais, le
 *   client paie exactement le prix affiché.
 *
 * Dans les deux cas, le net reversé à l'organisateur (subtotal_amount) ne change
 * pas : il vaut toujours prix de base − commission.
 */
class ServiceFeeBearerTest extends TestCase
{
    use RefreshDatabase;

    private function makeEvent(string $feeBearer): Event
    {
        $organizer = Organizer::create([
            'name' => 'Org Test',
            'slug' => 'org-test-' . uniqid(),
            'default_commission_percentage' => 10,
            'is_active' => true,
            'status' => 'active',
        ]);

        $categoryId = DB::table('event_categories')->insertGetId([
            'name' => 'Concerts',
            'slug' => 'concerts-' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'category_id' => $categoryId,
            'title' => 'Event Test',
            'slug' => 'event-test-' . uniqid(),
            'description' => 'desc',
            'status' => 'published',
            'approval_status' => 'approved',
            'is_active' => true,
            'use_variable_pricing' => false,
            'commission_percentage' => 10,
            'service_fee_bearer' => $feeBearer,
            'published_at' => now(),
        ]);

        $event->schedules()->create([
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(10)->addHours(4),
            'status' => 'active',
        ]);

        TicketType::create([
            'event_id' => $event->id,
            'name' => 'Standard',
            'price' => 1000,
            'available_quantity' => 100,
            'status' => 'active',
        ]);

        return $event->fresh(['ticketTypes', 'schedules']);
    }

    private function orderPayload(Event $event): array
    {
        return [
            'event_slug' => $event->slug,
            'ticket_type_id' => $event->ticketTypes->first()->id,
            'quantity' => 4,
            'guest_name' => 'John Doe',
            'guest_email' => 'john@example.test',
        ];
    }

    public function test_customer_bears_fee_adds_2_5_percent_to_total(): void
    {
        $event = $this->makeEvent('customer');

        $res = $this->postJson('/api/v1/guest/orders', $this->orderPayload($event));
        $res->assertStatus(201);

        $order = Order::latest('id')->first();

        // Base = 4 × 1000 = 4000 ; frais 2,5 % = 100 ; total = 4100
        $this->assertEquals(100.00, (float) $order->service_fee_amount);
        $this->assertEquals(4100.00, (float) $order->total_amount);
        $this->assertSame('customer', $order->service_fee_bearer);

        // Net organisateur inchangé = base − commission 10 % = 3600
        $this->assertEquals(3600.00, (float) $order->subtotal_amount);
        $this->assertEquals(400.00, (float) $order->fees_amount);
    }

    public function test_platform_bears_fee_client_pays_base_only(): void
    {
        $event = $this->makeEvent('platform');

        $res = $this->postJson('/api/v1/guest/orders', $this->orderPayload($event));
        $res->assertStatus(201);

        $order = Order::latest('id')->first();

        // Aucun frais ajouté : total = base = 4000
        $this->assertEquals(0.00, (float) $order->service_fee_amount);
        $this->assertEquals(4000.00, (float) $order->total_amount);
        $this->assertSame('platform', $order->service_fee_bearer);

        // Net organisateur identique au cas client
        $this->assertEquals(3600.00, (float) $order->subtotal_amount);
    }

    public function test_event_exposes_service_fee_percent_only_when_customer_bears(): void
    {
        $this->assertEquals(2.5, $this->makeEvent('customer')->service_fee_percent);
        $this->assertEquals(0.0, $this->makeEvent('platform')->service_fee_percent);
    }

    public function test_default_fee_bearer_is_platform_for_new_events(): void
    {
        $organizer = Organizer::create([
            'name' => 'Org D',
            'slug' => 'org-d-' . uniqid(),
            'default_commission_percentage' => 10,
            'is_active' => true,
            'status' => 'active',
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'No fee choice',
            'slug' => 'no-fee-' . uniqid(),
            'description' => 'desc',
            'status' => 'draft',
        ]);

        $this->assertSame('platform', $event->fresh()->service_fee_bearer);
        $this->assertFalse($event->fresh()->customerBearsServiceFee());
    }
}
