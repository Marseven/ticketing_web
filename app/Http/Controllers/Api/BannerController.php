<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * Récupérer les bannières actives pour une position donnée
     */
    public function getActive(Request $request)
    {
        $position = $request->query('position', 'home');

        $banners = Banner::active()
            ->position($position)
            ->orderBy('order', 'asc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($banner) {
                return [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'description' => $banner->description,
                    'image_url' => $banner->image_path ? asset('storage/' . $banner->image_path) : null,
                    'link_url' => $banner->link_url,
                    'position' => $banner->position,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $banners
        ]);
    }
}
