<?php

namespace Tests\Feature;

use App\Models\Checkin;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * La trace de celui qui scanne.
 *
 * À l'entrée d'un événement, plusieurs agents se relaient. Quand un litige
 * survient — un billet refusé à tort, deux personnes avec le même code — la
 * question est toujours la même : qui a validé, et quand ?
 *
 * L'auteur du scan était enregistré dans `checkins` depuis l'origine, mais il
 * ne ressortait nulle part : ni dans la réponse rendue à l'agent, ni sur les
 * documents. Il fallait ouvrir la base pour savoir.
 */
class TraceDuScanTest extends TestCase
{
    use RefreshDatabase;

    private Event $evenement;
    private Organizer $organisateur;
    private Ticket $billet;

    protected function setUp(): void
    {
        parent::setUp();

        UserType::firstOrCreate(['code' => 'admin'], ['name' => 'admin', 'label' => 'Admin']);
        Role::firstOrCreate(['slug' => Role::ADMIN], ['name' => Role::ADMIN, 'description' => 'admin']);

        $this->organisateur = Organizer::create([
            'name' => 'Prod', 'slug' => 'prod-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $this->evenement = Event::create([
            'organizer_id' => $this->organisateur->id,
            'title' => 'Soirée', 'slug' => 'soiree-' . uniqid(),
            'description' => 'x', 'status' => 'published',
            'approval_status' => 'approved', 'is_active' => true,
        ]);

        EventSchedule::create([
            'event_id' => $this->evenement->id,
            'starts_at' => now()->subHour(), 'ends_at' => now()->addHours(4),
            'status' => 'active',
        ]);

        $type = TicketType::create([
            'event_id' => $this->evenement->id, 'name' => 'Standard', 'price' => 5000,
            'currency' => 'XAF', 'status' => 'active', 'available_quantity' => 50,
        ]);

        $commande = Order::create([
            'organizer_id' => $this->organisateur->id, 'currency' => 'XAF',
            'subtotal_amount' => 5000, 'fees_amount' => 0, 'commission_percentage' => 10,
            'tax_amount' => 0, 'total_amount' => 5000, 'status' => 'paid',
            'reference' => 'ORD-' . strtoupper(uniqid()), 'placed_at' => now(),
            'is_guest_order' => true, 'guest_name' => 'Client',
        ]);

        $this->billet = Ticket::create([
            'order_id' => $commande->id, 'event_id' => $this->evenement->id,
            'ticket_type_id' => $type->id, 'code' => 'TKT-' . strtoupper(uniqid()),
            'status' => 'issued',
        ]);
    }

    private function agent(string $nom): User
    {
        $user = User::create([
            'name' => $nom, 'email' => Str()->slug($nom) . '-' . uniqid() . '@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        $user->user_type_id = UserType::where('name', 'admin')->value('id');
        $user->save();
        $user->roles()->attach(Role::where('slug', Role::ADMIN)->value('id'));

        return $user->fresh();
    }

    private function scanner(string $code)
    {
        return $this->postJson('/api/v1/scans', [
            'qr_code' => $code,
            'scanned_at' => now()->toIso8601String(),
            'device_id' => 'portique-1',
        ]);
    }

    public function test_le_scan_enregistre_son_auteur(): void
    {
        $agent = $this->agent('Portier Nord');
        Sanctum::actingAs($agent);

        $this->scanner($this->billet->code)->assertOk();

        $trace = Checkin::where('ticket_id', $this->billet->id)->first();

        $this->assertNotNull($trace, 'le scan laisse une trace');
        $this->assertSame($agent->id, $trace->scanned_by);
        $this->assertSame('portique-1', $trace->device_id, 'et l\'appareil utilisé');
    }

    public function test_la_reponse_nomme_celui_qui_a_scanne(): void
    {
        // Sans cela, l'agent ne voit pas son propre nom, et personne ne peut
        // vérifier sur place qui vient de laisser entrer.
        Sanctum::actingAs($this->agent('Portier Nord'));

        $reponse = $this->scanner($this->billet->code)->assertOk();

        $this->assertSame('Portier Nord', $reponse->json('scanned_by'));
    }

    public function test_un_second_scan_nomme_celui_qui_avait_laisse_entrer(): void
    {
        // La question posée quand deux personnes se présentent avec le même
        // billet : qui a ouvert la porte la première fois ?
        Sanctum::actingAs($this->agent('Portier Nord'));
        $this->scanner($this->billet->code)->assertOk();

        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($this->agent('Portier Sud'));

        $rescan = $this->scanner($this->billet->code);

        $this->assertSame('duplicate', $rescan->json('result'));
        $this->assertSame('Portier Nord', $rescan->json('first_scan_by'));
    }

    public function test_chaque_agent_garde_sa_propre_trace(): void
    {
        // Deux agents se relaient sur la même porte : chaque validation doit
        // rester attribuée à celui qui l'a faite.
        $nord = $this->agent('Portier Nord');
        $sud = $this->agent('Portier Sud');

        $second = Ticket::create([
            'order_id' => $this->billet->order_id, 'event_id' => $this->evenement->id,
            'ticket_type_id' => $this->billet->ticket_type_id,
            'code' => 'TKT-' . strtoupper(uniqid()), 'status' => 'issued',
        ]);

        Sanctum::actingAs($nord);
        $this->scanner($this->billet->code)->assertOk();

        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($sud);
        $this->scanner($second->code)->assertOk();

        $this->assertSame($nord->id, Checkin::where('ticket_id', $this->billet->id)->value('scanned_by'));
        $this->assertSame($sud->id, Checkin::where('ticket_id', $second->id)->value('scanned_by'));
    }

    public function test_un_refus_laisse_aussi_sa_trace(): void
    {
        // Un billet refusé est exactement ce qu'on veut pouvoir retrouver :
        // c'est là que naissent les contestations.
        $agent = $this->agent('Portier Nord');
        Sanctum::actingAs($agent);

        $this->billet->update(['status' => 'void']);

        $this->scanner($this->billet->code);

        $trace = Checkin::where('ticket_id', $this->billet->id)->first();

        $this->assertNotNull($trace);
        $this->assertSame('invalid', $trace->result);
        $this->assertSame($agent->id, $trace->scanned_by);
    }
}
