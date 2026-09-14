<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Branding piloté par config : défauts MyTicketO, surcharge en base (scope
 * system, clés branding.*), exposé publiquement pour le SPA.
 */
class BrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_endpoint_returns_defaults(): void
    {
        $this->getJson('/api/v1/branding')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.app_name', 'MyTicketO')
            ->assertJsonPath('data.contact_email', 'contact@primea.ga');
    }

    public function test_override_is_applied_and_falls_back_for_unset_keys(): void
    {
        Setting::setBranding('app_name', 'Festival Pro');
        Setting::setBranding('contact_email', 'hello@festival.pro');
        Cache::forget('branding');

        $b = Setting::branding();
        $this->assertSame('Festival Pro', $b['app_name']);         // surcharge
        $this->assertSame('hello@festival.pro', $b['contact_email']);
        $this->assertSame('La Billetterie', $b['header_title']);   // défaut conservé
    }

    public function test_unknown_keys_are_ignored(): void
    {
        Setting::setBranding('not_a_field', 'x');
        Cache::forget('branding');

        $this->assertArrayNotHasKey('not_a_field', Setting::branding());
    }

    public function test_color_vars_convert_hex_to_rgb_channels(): void
    {
        // Défauts
        $vars = Setting::brandColorVars();
        $this->assertSame('0 75 94', $vars['--brand-primary-rgb']);      // #004B5E
        $this->assertSame('245 192 112', $vars['--brand-accent-rgb']);   // #F5C070
        $this->assertSame('31 158 154', $vars['--brand-secondary-rgb']); // #1F9E9A

        // Surcharge d'une couleur
        Setting::setBranding('color_primary', '#FF0000');
        Cache::forget('branding');
        $this->assertSame('255 0 0', Setting::brandColorVars()['--brand-primary-rgb']);
    }

    public function test_invalid_hex_falls_back(): void
    {
        $this->assertSame('0 75 94', Setting::hexToRgbChannels('not-a-hex'));
        $this->assertSame('255 255 255', Setting::hexToRgbChannels('#fff'));
    }
}
