<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Banner::query()->orderBy('order', 'asc')->orderBy('created_at', 'desc');

        // Filtrer par position si spécifié
        if ($request->has('position') && $request->position !== 'all') {
            $query->where('position', $request->position);
        }

        // Filtrer par statut si spécifié
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $banners = $query->get();

        return response()->json([
            'success' => true,
            'data' => $banners
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'link_url' => 'nullable|url',
            'position' => 'required|in:home,events,checkout,all',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Upload de l'image
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('banners', $imageName, 'public');
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Image is required'
            ], 422);
        }

        $banner = Banner::create([
            'title' => $request->title,
            'description' => $request->description,
            'image_path' => $imagePath,
            'link_url' => $request->link_url,
            'position' => $request->position,
            'order' => $request->order ?? 0,
            'is_active' => $request->is_active ?? true,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bannière créée avec succès',
            'data' => $banner
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Bannière introuvable'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $banner
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Bannière introuvable'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'link_url' => 'nullable|url',
            'position' => 'nullable|in:home,events,checkout,all',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Upload de la nouvelle image si fournie
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image
            if ($banner->image_path && Storage::disk('public')->exists($banner->image_path)) {
                Storage::disk('public')->delete($banner->image_path);
            }

            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('banners', $imageName, 'public');
            $banner->image_path = $imagePath;
        }

        // Mise à jour des autres champs
        if ($request->has('title')) {
            $banner->title = $request->title;
        }
        if ($request->has('description')) {
            $banner->description = $request->description;
        }
        if ($request->has('link_url')) {
            $banner->link_url = $request->link_url;
        }
        if ($request->has('position')) {
            $banner->position = $request->position;
        }
        if ($request->has('order')) {
            $banner->order = $request->order;
        }
        if ($request->has('is_active')) {
            $banner->is_active = $request->boolean('is_active');
        }
        if ($request->has('start_date')) {
            $banner->start_date = $request->start_date;
        }
        if ($request->has('end_date')) {
            $banner->end_date = $request->end_date;
        }

        $banner->save();

        return response()->json([
            'success' => true,
            'message' => 'Bannière mise à jour avec succès',
            'data' => $banner
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Bannière introuvable'
            ], 404);
        }

        // Supprimer l'image
        if ($banner->image_path && Storage::disk('public')->exists($banner->image_path)) {
            Storage::disk('public')->delete($banner->image_path);
        }

        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bannière supprimée avec succès'
        ]);
    }

    /**
     * Toggle the active status of a banner
     */
    public function toggleActive(string $id)
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Bannière introuvable'
            ], 404);
        }

        $banner->is_active = !$banner->is_active;
        $banner->save();

        return response()->json([
            'success' => true,
            'message' => 'Statut de la bannière mis à jour',
            'data' => $banner
        ]);
    }
}
