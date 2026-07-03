<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Événements multi-dates : rattachement des billets à une date (schedule)
 * et récapitulatif des ventes par date.
 */
class MultiDateSalesTest extends TestCase
{
    use RefreshDatabase;

    private function makeEventWithSchedules(int $count): Event
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Multi', 'slug' => 'multi-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
        ]);

        for ($i = 1; $i <= $count; $i++) {
            EventSchedule::create([
                'event_id' => $event->id,
                'starts_at' => now()->addDays($i)->setTime(20, 0),
                'ends_at' => now()->addDays($i)->setTime(23, 0),
                'status' => 'active',
            ]);
        }

        return $event->fresh('schedules');
    }

    public function test_tickets_are_attributed_to_their_own_schedule(): void
    {
        $event = $this->makeEventWithSchedules(2);
        $schedules = $event->schedules;

        $tt = TicketType::create([
            'event_id' => $event->id, 'name' => 'Std', 'price' => 1000,
            'currency' => 'XAF', 'status' => 'active',
        ]);

        $order = Order::create([
            'organizer_id' => $event->organizer_id, 'buyer_id' => null,
            'currency' => 'XAF', 'subtotal_amount' => 3600, 'fees_amount' => 400,
            'commission_percentage' => 10, 'tax_amount' => 0, 'total_amount' => 4000,
            'status' => 'paid', 'reference' => 'ORD-' . uniqid(),
            'placed_at' => now(), 'is_guest_order' => true,
        ]);

        // 3 billets sur la 1re date, 1 sur la 2e
        foreach (range(1, 3) as $n) {
            Ticket::create([
                'order_id' => $order->id, 'event_id' => $event->id, 'ticket_type_id' => $tt->id,
                'schedule_id' => $schedules[0]->id, 'code' => 'T1' . $n, 'status' => 'issued',
            ]);
        }
        Ticket::create([
            'order_id' => $order->id, 'event_id' => $event->id, 'ticket_type_id' => $tt->id,
            'schedule_id' => $schedules[1]->id, 'code' => 'T2', 'status' => 'issued',
        ]);

        $soldDate1 = Ticket::where('event_id', $event->id)
            ->where('schedule_id', $schedules[0]->id)
            ->whereIn('status', ['issued', 'used'])->count();
        $soldDate2 = Ticket::where('event_id', $event->id)
            ->where('schedule_id', $schedules[1]->id)
            ->whereIn('status', ['issued', 'used'])->count();

        $this->assertSame(3, $soldDate1);
        $this->assertSame(1, $soldDate2);
    }
}
