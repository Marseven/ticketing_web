<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Réglages clé/valeur (table `settings` existante, scopée system/organizer/…).
 * Le branding (nom, slogan, logos, favicon, email, métadonnées) est piloté ici
 * en scope `system`, clés préfixées `branding.`. Les valeurs par défaut vivent
 * dans le code (BRANDING_DEFAULTS) ; la base ne stocke que les surcharges.
 * Résilient : renvoie les défauts si la table est absente (pré-migration).
 */
class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = ['scope_type', 'scope_id', 'key', 'value'];

    private const BRANDING_PREFIX = 'branding.';

    /** Valeurs de marque par défaut (Primea). */
    public const BRANDING_DEFAULTS = [
        'app_name' => 'Primea',
        'header_title' => 'La Billetterie',
        'header_subtitle' => 'Simple, Rapide et Sécurisée',
        'contact_email' => 'contact@primea.ga',
        'logo_url' => '/images/logo.png?v=3',
        'logo_white_url' => '/images/logo_white.png?v=3',
        'favicon_url' => '/images/ico.png?v=3',
        'meta_title' => "Primea - Se procurer un ticket n'a jamais été aussi simple",
        'meta_description' => "Se procurer un ticket n'a jamais été aussi simple ! Achetez vos billets d'événements en ligne au Gabon.",
        'og_image' => '/images/ico.png?v=3',
        // Couleurs de marque Primea (hex) — pilotent les variables CSS --brand-*.
        'color_primary' => '#272d63',   // bleu marine
        'color_accent' => '#fab511',    // jaune
        'color_secondary' => '#1a1f4a', // bleu marine foncé
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('branding'));
        static::deleted(fn () => Cache::forget('branding'));
    }

    /** Branding effectif : défauts fusionnés avec les surcharges en base. */
    public static function branding(): array
    {
        return Cache::rememberForever('branding', function () {
            $stored = [];
            try {
                if (Schema::hasTable('settings')) {
                    $rows = static::where('scope_type', 'system')
                        ->where('key', 'like', self::BRANDING_PREFIX . '%')
                        ->pluck('value', 'key');
                    foreach ($rows as $key => $value) {
                        $bare = substr($key, strlen(self::BRANDING_PREFIX));
                        if (array_key_exists($bare, self::BRANDING_DEFAULTS) && $value !== null && $value !== '') {
                            $stored[$bare] = $value;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // base indisponible → défauts
            }

            return array_merge(self::BRANDING_DEFAULTS, $stored);
        });
    }

    /**
     * Variables CSS de couleur (canaux RGB) pour injection dans :root.
     * Ex: ['--brand-primary-rgb' => '0 75 94', ...].
     */
    public static function brandColorVars(): array
    {
        $b = self::branding();
        return [
            '--brand-primary-rgb' => self::hexToRgbChannels($b['color_primary'] ?? '#272d63', '39 45 99'),
            '--brand-accent-rgb' => self::hexToRgbChannels($b['color_accent'] ?? '#fab511', '250 181 17'),
            '--brand-secondary-rgb' => self::hexToRgbChannels($b['color_secondary'] ?? '#1a1f4a', '26 31 74'),
        ];
    }

    /** Convertit "#RRGGBB" (ou "#RGB") en canaux "R G B" ; fallback si invalide. */
    public static function hexToRgbChannels(string $hex, string $fallback = '39 45 99'): string
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return $fallback;
        }
        return hexdec(substr($hex, 0, 2)) . ' ' . hexdec(substr($hex, 2, 2)) . ' ' . hexdec(substr($hex, 4, 2));
    }

    /** Écrire une surcharge de branding (clé connue uniquement). */
    public static function setBranding(string $key, ?string $value): void
    {
        if (!array_key_exists($key, self::BRANDING_DEFAULTS)) {
            return;
        }
        static::updateOrCreate(
            ['scope_type' => 'system', 'scope_id' => null, 'key' => self::BRANDING_PREFIX . $key],
            ['value' => $value]
        );
    }
}
