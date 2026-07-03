<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketPrice;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tarification dynamique / prévente : le prix effectif suit la fenêtre
 * temporelle courante (TicketType::getPriceFor).
 */
class DynamicPricingTest extends TestCase
{
    use RefreshDatabase;

    private function makeTicketType(bool $variable): TicketType
    {
        $organizer = Organizer::create([
            'name' => 'Org Test',
            'slug' => 'org-test-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Event Test',
            'slug' => 'event-test-' . uniqid(),
            'description' => 'x',
            'status' => 'published',
            'approval_status' => 'approved',
            'use_variable_pricing' => $variable,
        ]);

        return TicketType::create([
            'event_id' => $event->id,
            'name' => 'Standard',
            'price' => 3000,
            'currency' => 'XAF',
            'status' => 'active',
        ]);
    }

    public function test_prevente_price_applies_before_switch_and_full_price_after(): void
    {
        $tt = $this->makeTicketType(true);

        // Prévente : 2000 jusqu'à demain
        TicketPrice::create([
            'ticket_type_id' => $tt->id,
            'price' => 2000,
            'currency' => 'XAF',
            'valid_until' => now()->addDay(),
            'priority' => 0,
            'status' => 'active',
        ]);

        // Plein tarif : 3000 à partir de demain
        TicketPrice::create([
            'ticket_type_id' => $tt->id,
            'price' => 3000,
            'currency' => 'XAF',
            'valid_from' => now()->addDay(),
            'priority' => 0,
            'status' => 'active',
        ]);

        // Maintenant => prévente
        $this->assertSame(2000.0, (float) $tt->getPriceFor(null, null, now()->toDateTimeString()));

        // Après-demain => plein tarif
        $this->assertSame(3000.0, (float) $tt->getPriceFor(null, null, now()->addDays(2)->toDateTimeString()));
    }

    public function test_static_price_when_variable_pricing_disabled(): void
    {
        $tt = $this->makeTicketType(false);

        TicketPrice::create([
            'ticket_type_id' => $tt->id,
            'price' => 2000,
            'currency' => 'XAF',
            'valid_until' => now()->addDay(),
            'status' => 'active',
        ]);

        // Tarification variable désactivée => prix de base, on ignore les paliers
        $this->assertSame(3000.0, (float) $tt->getPriceFor(null, null, now()->toDateTimeString()));
    }
}
