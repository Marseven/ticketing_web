<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class HeroBannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = HeroBanner::query()->ordered();

        // Filtrer par statut si spécifié
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filtrer par type si spécifié
        if ($request->has('type') && in_array($request->type, ['image', 'video'])) {
            $query->where('type', $request->type);
        }

        $heroBanners = $query->get();

        return response()->json([
            'success' => true,
            'data' => $heroBanners
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'type' => 'required|in:image,video',
            'media_file' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,mp4,webm,ogg|max:10240',
            'media_url' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Déterminer l'URL du média
        $mediaUrl = null;

        if ($request->hasFile('media_file')) {
            // Upload du fichier
            $file = $request->file('media_file');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('hero_banners', $fileName, 'public');
            $mediaUrl = '/storage/' . $filePath;
        } elseif ($request->filled('media_url')) {
            // Utiliser l'URL fournie
            $mediaUrl = $request->media_url;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez fournir un fichier ou une URL de média'
            ], 422);
        }

        $heroBanner = HeroBanner::create([
            'title' => $request->title,
            'type' => $request->type,
            'media_url' => $mediaUrl,
            'display_order' => $request->display_order ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Hero banner créé avec succès',
            'data' => $heroBanner
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $heroBanner = HeroBanner::find($id);

        if (!$heroBanner) {
            return response()->json([
                'success' => false,
                'message' => 'Hero banner introuvable'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $heroBanner
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $heroBanner = HeroBanner::find($id);

        if (!$heroBanner) {
            return response()->json([
                'success' => false,
                'message' => 'Hero banner introuvable'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'type' => 'nullable|in:image,video',
            'media_file' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,mp4,webm,ogg|max:10240',
            'media_url' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Upload du nouveau média si fourni
        if ($request->hasFile('media_file')) {
            // Supprimer l'ancien fichier si c'était un fichier local
            if ($heroBanner->media_url && str_starts_with($heroBanner->media_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $heroBanner->media_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $file = $request->file('media_file');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('hero_banners', $fileName, 'public');
            $heroBanner->media_url = '/storage/' . $filePath;
        } elseif ($request->filled('media_url')) {
            // Mettre à jour avec la nouvelle URL
            $heroBanner->media_url = $request->media_url;
        }

        // Mise à jour des autres champs
        if ($request->has('title')) {
            $heroBanner->title = $request->title;
        }
        if ($request->has('type')) {
            $heroBanner->type = $request->type;
        }
        if ($request->has('display_order')) {
            $heroBanner->display_order = $request->display_order;
        }
        if ($request->has('is_active')) {
            $heroBanner->is_active = $request->boolean('is_active');
        }

        $heroBanner->save();

        return response()->json([
            'success' => true,
            'message' => 'Hero banner mis à jour avec succès',
            'data' => $heroBanner
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $heroBanner = HeroBanner::find($id);

        if (!$heroBanner) {
            return response()->json([
                'success' => false,
                'message' => 'Hero banner introuvable'
            ], 404);
        }

        // Supprimer le fichier si c'est un fichier local
        if ($heroBanner->media_url && str_starts_with($heroBanner->media_url, '/storage/')) {
            $filePath = str_replace('/storage/', '', $heroBanner->media_url);
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
        }

        $heroBanner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Hero banner supprimé avec succès'
        ]);
    }

    /**
     * Toggle the active status of a hero banner
     */
    public function toggleActive(string $id)
    {
        $heroBanner = HeroBanner::find($id);

        if (!$heroBanner) {
            return response()->json([
                'success' => false,
                'message' => 'Hero banner introuvable'
            ], 404);
        }

        $heroBanner->is_active = !$heroBanner->is_active;
        $heroBanner->save();

        return response()->json([
            'success' => true,
            'message' => 'Statut du hero banner mis à jour',
            'data' => $heroBanner
        ]);
    }

    /**
     * Get the active hero banner for the home page
     */
    public function getActive()
    {
        $heroBanner = HeroBanner::active()->ordered()->first();

        if (!$heroBanner) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun hero banner actif'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $heroBanner
        ]);
    }
}
