<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Identité de marque configurable à chaud : nom, slogan, email, logos, favicon,
 * métadonnées. La lecture est publique (le SPA en a besoin) ; l'écriture est
 * réservée à l'admin.
 */
class BrandingController extends Controller
{
    /** Réglages texte éditables. */
    private const TEXT_FIELDS = [
        'app_name', 'header_title', 'header_subtitle', 'contact_email',
        'meta_title', 'meta_description',
        'color_primary', 'color_accent', 'color_secondary',
    ];

    /** Assets image (clé de branding => nom de fichier stocké). */
    private const ASSET_FIELDS = ['logo_url', 'logo_white_url', 'favicon_url', 'og_image'];

    /** Branding effectif (public, sans auth). */
    public function show(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Setting::branding()]);
    }

    /** Mettre à jour les champs texte (admin). */
    public function update(Request $request): JsonResponse
    {
        $hex = 'nullable|regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/';
        $data = $request->validate([
            'app_name' => 'nullable|string|max:100',
            'header_title' => 'nullable|string|max:100',
            'header_subtitle' => 'nullable|string|max:150',
            'contact_email' => 'nullable|email|max:150',
            'meta_title' => 'nullable|string|max:200',
            'meta_description' => 'nullable|string|max:300',
            'color_primary' => $hex,
            'color_accent' => $hex,
            'color_secondary' => $hex,
        ]);

        foreach (self::TEXT_FIELDS as $field) {
            if ($request->has($field)) {
                Setting::setBranding($field, $data[$field] ?? null);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Identité mise à jour.',
            'data' => Setting::branding(),
        ]);
    }

    /** Uploader un asset (logo / logo blanc / favicon / image OG) (admin). */
    public function uploadAsset(Request $request): JsonResponse
    {
        $request->validate([
            'field' => 'required|in:' . implode(',', self::ASSET_FIELDS),
            'file' => 'required|file|mimes:png,jpg,jpeg,webp,svg,ico|max:2048',
        ]);

        $field = $request->input('field');
        $ext = $request->file('file')->getClientOriginalExtension();
        // Nom stable par champ + horodatage pour casser le cache navigateur.
        $name = $field . '-' . now()->format('YmdHis') . '.' . $ext;
        $request->file('file')->storeAs('public/branding', $name);

        $url = '/storage/branding/' . $name;
        Setting::setBranding($field, $url);

        return response()->json([
            'success' => true,
            'message' => 'Fichier mis à jour.',
            'data' => ['field' => $field, 'url' => $url, 'branding' => Setting::branding()],
        ]);
    }
}
