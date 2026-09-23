<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * QR code menant à la billetterie d'un événement.
 *
 * Il se pose sur une affiche, un flyer ou un chevalet de table : la personne
 * scanne et tombe directement sur la page d'achat. C'est la même adresse que
 * le lien partagé par message, `/{slug}`.
 */
class EventShareQrTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $type = UserType::firstOrCreate(['code' => 'admin'], ['name' => 'admin', 'label' => 'Admin']);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin-' . uniqid() . '@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        $admin->user_type_id = $type->id;
        $admin->save();

        $role = Role::firstOrCreate(
            ['name' => Role::ADMIN],
            ['slug' => Role::ADMIN, 'label' => 'Administrateur', 'description' => 'Accès complet']
        );
        $admin->roles()->syncWithoutDetaching([$role->id]);

        return $admin->fresh('roles');
    }

    private function event(): Event
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $category = EventCategory::firstOrCreate(['slug' => 'concert'], ['name' => 'Concert', 'is_active' => true]);

        return Event::create([
            'organizer_id' => $organizer->id, 'category_id' => $category->id,
            'title' => 'Nuit des Champions', 'slug' => 'nuit-des-champions',
            'description' => 'Gala', 'status' => 'published', 'approval_status' => 'approved',
        ]);
    }

    public function test_it_points_at_the_purchase_page(): void
    {
        config(['app.url' => 'https://primea.ga']);
        Sanctum::actingAs($this->admin());

        $data = $this->getJson('/api/v1/admin/events/' . $this->event()->id . '/share-qr')
            ->assertOk()
            ->json('data');

        // `/{slug}` ouvre l'achat ; c'est bien là qu'on veut envoyer les gens.
        $this->assertSame('https://primea.ga/nuit-des-champions', $data['url']);
        $this->assertSame('Nuit des Champions', $data['title']);
    }

    public function test_the_image_is_a_png_ready_to_display(): void
    {
        Sanctum::actingAs($this->admin());

        $qr = $this->getJson('/api/v1/admin/events/' . $this->event()->id . '/share-qr')
            ->assertOk()
            ->json('data.qr');

        $this->assertStringStartsWith('data:image/png;base64,', $qr);

        // Le PNG doit être réellement décodable, pas une chaîne quelconque.
        $binary = base64_decode(substr($qr, strlen('data:image/png;base64,')));
        $this->assertNotFalse(imagecreatefromstring($binary));
    }

    public function test_it_can_be_downloaded_for_printing(): void
    {
        Sanctum::actingAs($this->admin());

        $response = $this->get('/api/v1/admin/events/' . $this->event()->id . '/share-qr?format=png')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->assertStringContainsString(
            'attachment; filename="nuit-des-champions-qr.png"',
            $response->headers->get('Content-Disposition')
        );

        $image = imagecreatefromstring($response->getContent());
        $this->assertNotFalse($image);
        // Assez grand pour une affiche : un QR de 300 px imprimé est illisible.
        $this->assertGreaterThan(500, imagesx($image));
    }

    public function test_an_unknown_event_is_refused(): void
    {
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/v1/admin/events/999999/share-qr')->assertStatus(404);
    }

    public function test_it_is_reserved_to_administrators(): void
    {
        $this->getJson('/api/v1/admin/events/' . $this->event()->id . '/share-qr')
            ->assertStatus(401);
    }
}
