<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Services\QRCodeService;
use App\Services\TicketValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le QR sécurisé (format EMVCO) et le code simple.
 *
 * Ce que les billets portent aujourd'hui, c'est le CODE SIMPLE : l'image
 * téléchargée et les billets imprimés encodent `tickets.code`, et c'est ce
 * chemin-là qui doit rester valide coûte que coûte.
 *
 * Le QR sécurisé, lui, est produit par `generateTicketQRCode` mais n'est
 * affiché nulle part : le front ne lit jamais `qr_code_content`. Il n'a donc
 * jamais été scanné en vrai — ce qui explique que deux défauts y soient passés
 * inaperçus. Le premier est corrigé ici, le second est consigné tel quel.
 */
class SecureQrRoundTripTest extends TestCase
{
    use RefreshDatabase;

    private function ticket(): Ticket
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Chill Expo', 'slug' => 'chill-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
        ]);

        $schedule = EventSchedule::create([
            'event_id' => $event->id,
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(4),
            'status' => 'active',
        ]);

        $type = TicketType::create([
            'event_id' => $event->id, 'name' => 'Standard', 'price' => 1000,
            'currency' => 'XAF', 'status' => 'active',
        ]);

        $order = Order::create([
            'organizer_id' => $organizer->id, 'buyer_id' => null, 'currency' => 'XAF',
            'subtotal_amount' => 1000, 'fees_amount' => 0, 'tax_amount' => 0,
            'total_amount' => 1000, 'status' => 'paid',
            'reference' => 'ORD-' . strtoupper(uniqid()), 'placed_at' => now(),
            'is_guest_order' => true, 'guest_name' => 'Mazzarine',
            'guest_email' => 'm@example.test',
        ]);

        return Ticket::create([
            'order_id' => $order->id, 'event_id' => $event->id,
            'ticket_type_id' => $type->id, 'schedule_id' => $schedule->id,
            'buyer_id' => null, 'code' => 'TKT-' . strtoupper(substr(uniqid(), -8)),
            'status' => 'issued', 'issued_at' => now(), 'ticket_source' => 'online',
        ]);
    }

    public function test_the_plain_code_validates(): void
    {
        // C'est le chemin réellement emprunté par les billets en circulation.
        $ticket = $this->ticket();

        $result = app(TicketValidationService::class)->validate($ticket->code, [
            'enforce_schedule' => false,
        ]);

        $this->assertSame('valid', $result['result']);
        $this->assertSame($ticket->code, $result['ticket']['code']);
    }

    public function test_the_checksum_of_a_secure_qr_is_computed_correctly(): void
    {
        $service = app(QRCodeService::class);
        $qr = $service->generateTicketQRCode($this->ticket());

        $this->assertStringStartsWith('000201', $qr);

        // Le CRC porte sur toute la chaîne sauf ses quatre derniers
        // caractères. La balise « 6304 » y est déjà : la rajouter avant de
        // calculer la comptait deux fois, et AUCUN QR émis ne se relisait.
        $decoded = $service->decodeTicketQRCode($qr);

        $this->assertNotSame('CRC_MISMATCH', $decoded['error'] ?? null);
        $this->assertTrue($decoded['valid']);
    }

    public function test_a_data_object_longer_than_99_characters_breaks_the_format(): void
    {
        // Défaut restant, consigné plutôt que laissé invisible : la longueur
        // d'un objet EMVCO tient sur DEUX chiffres, donc une valeur ne peut
        // pas dépasser 99 caractères. Le gabarit « données additionnelles »
        // (ID 62) en fait 110 : sa longueur déborde, le découpage part de
        // travers et la référence du billet devient introuvable.
        //
        // Sans conséquence aujourd'hui — ce QR n'est affiché nulle part — mais
        // il faudra raccourcir ce gabarit avant de s'en servir.
        $service = app(QRCodeService::class);
        $ticket = $this->ticket();

        $method = new \ReflectionMethod($service, 'buildAdditionalDataTemplate');
        $method->setAccessible(true);
        $template = $method->invoke($service, $ticket);

        $this->assertGreaterThan(
            99,
            strlen($template),
            'si ce gabarit repasse sous 99 caractères, le QR sécurisé redevient '
            . 'exploitable : reprendre le test d\'aller-retour complet'
        );

        // Conséquence directe : la référence ne ressort pas du décodage.
        $decoded = $service->decodeTicketQRCode($service->generateTicketQRCode($ticket));
        $this->assertNull($decoded['reference_id'] ?? null);
    }

    public function test_an_unknown_value_is_refused_without_crashing(): void
    {
        $result = app(TicketValidationService::class)->validate('000201NIMPORTEQUOI', [
            'enforce_schedule' => false,
        ]);

        $this->assertContains($result['result'], ['invalid', 'not_found']);
    }
}
