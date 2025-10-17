<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageController extends Controller
{
    /**
     * Types d'entités supportées pour les images
     */
    const SUPPORTED_TYPES = ['events', 'venues', 'users', 'organizers'];
    
    /**
     * Tailles d'images par type
     */
    const IMAGE_SIZES = [
        'events' => [
            'thumbnail' => [300, 200],
            'medium' => [800, 600],
            'large' => [1200, 800]
        ],
        'venues' => [
            'thumbnail' => [200, 200],
            'medium' => [600, 400]
        ],
        'users' => [
            'thumbnail' => [150, 150],
            'medium' => [300, 300]
        ],
        'organizers' => [
            'thumbnail' => [200, 200],
            'medium' => [400, 400]
        ]
    ];

    /**
     * Upload d'une image
     */
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            'type' => 'required|string|in:' . implode(',', self::SUPPORTED_TYPES),
            'entity_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $type = $request->type;
            $image = $request->file('image');
            
            // Générer un nom unique pour l'image
            $filename = $this->generateFilename($image->getClientOriginalExtension());
            
            // Créer le dossier si nécessaire
            $uploadPath = "images/{$type}";
            Storage::disk('public')->makeDirectory($uploadPath);
            
            // Convertir et sauvegarder l'image originale
            $originalPath = "{$uploadPath}/{$filename}";
            try {
                $manager = new ImageManager(new Driver());
                $imageObj = $manager->read($image);
                
                // Convertir selon l'extension
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                switch ($extension) {
                    case 'png':
                        $convertedImage = $imageObj->toPng();
                        break;
                    case 'gif':
                        $convertedImage = $imageObj->toGif();
                        break;
                    case 'webp':
                        $convertedImage = $imageObj->toWebp(85);
                        break;
                    default:
                        $convertedImage = $imageObj->toJpeg(85);
                        break;
                }
                
                Storage::disk('public')->put($originalPath, $convertedImage);
            } catch (\Exception $e) {
                // Fallback: sauvegarder tel quel
                Storage::disk('public')->put($originalPath, file_get_contents($image));
            }
            
            // Générer les différentes tailles
            $imagePaths = $this->generateImageSizes($originalPath, $type, $uploadPath, $filename);
            
            // Ajouter l'image originale
            $imagePaths['original'] = $originalPath;
            
            return response()->json([
                'success' => true,
                'message' => 'Image uploadée avec succès',
                'data' => [
                    'filename' => $filename,
                    'paths' => $imagePaths,
                    'urls' => $this->getImageUrls($imagePaths)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validation d'une URL d'image
     */
    public function validateUrl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'type' => 'required|string|in:' . implode(',', self::SUPPORTED_TYPES),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'URL invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $url = $request->url;
            
            // Vérifier si l'URL est accessible et contient une image
            $headers = @get_headers($url, 1);
            
            if (!$headers || strpos($headers[0], '200') === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'URL n\'est pas accessible'
                ], 400);
            }
            
            $contentType = $headers['Content-Type'] ?? '';
            if (is_array($contentType)) {
                $contentType = $contentType[0];
            }
            
            if (!str_starts_with($contentType, 'image/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'URL ne pointe pas vers une image valide'
                ], 400);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'URL d\'image valide',
                'data' => [
                    'url' => $url,
                    'content_type' => $contentType
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une image
     */
    public function delete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string',
            'type' => 'required|string|in:' . implode(',', self::SUPPORTED_TYPES),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $type = $request->type;
            $filename = $request->filename;
            $uploadPath = "images/{$type}";
            
            // Supprimer toutes les tailles d'images
            $sizes = array_keys(self::IMAGE_SIZES[$type] ?? []);
            foreach ($sizes as $size) {
                $sizePath = "{$uploadPath}/{$size}_{$filename}";
                if (Storage::disk('public')->exists($sizePath)) {
                    Storage::disk('public')->delete($sizePath);
                }
            }
            
            // Supprimer l'image originale
            $originalPath = "{$uploadPath}/{$filename}";
            if (Storage::disk('public')->exists($originalPath)) {
                Storage::disk('public')->delete($originalPath);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Image supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer un nom de fichier unique
     */
    private function generateFilename(string $extension): string
    {
        // Préserver l'extension d'origine pour éviter les problèmes d'URL
        return Str::random(40) . '.' . strtolower($extension);
    }

    /**
     * Générer les différentes tailles d'images
     */
    private function generateImageSizes(string $originalPath, string $type, string $uploadPath, string $filename): array
    {
        $imagePaths = [];
        $sizes = self::IMAGE_SIZES[$type] ?? [];
        
        foreach ($sizes as $sizeName => [$width, $height]) {
            try {
                // Créer une copie redimensionnée avec Intervention Image v3
                $manager = new ImageManager(new Driver());
                $fullPath = storage_path('app/public/' . $originalPath);
                $imageObj = $manager->read($fullPath)->cover($width, $height);
                
                // Convertir selon l'extension d'origine
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                switch ($extension) {
                    case 'png':
                        $resizedImage = $imageObj->toPng();
                        break;
                    case 'gif':
                        $resizedImage = $imageObj->toGif();
                        break;
                    case 'webp':
                        $resizedImage = $imageObj->toWebp(85);
                        break;
                    default:
                        $resizedImage = $imageObj->toJpeg(85);
                        break;
                }
                
                $sizePath = "{$uploadPath}/{$sizeName}_{$filename}";
                Storage::disk('public')->put($sizePath, $resizedImage);
                $imagePaths[$sizeName] = $sizePath;
                
            } catch (\Exception $e) {
                // Si Intervention Image n'est pas disponible, copier l'original
                $sizePath = "{$uploadPath}/{$sizeName}_{$filename}";
                Storage::disk('public')->copy("{$uploadPath}/{$filename}", $sizePath);
                $imagePaths[$sizeName] = $sizePath;
            }
        }
        
        return $imagePaths;
    }

    /**
     * Convertir les chemins en URLs publiques
     */
    private function getImageUrls(array $paths): array
    {
        $urls = [];
        foreach ($paths as $size => $path) {
            $urls[$size] = Storage::disk('public')->url($path);
        }
        return $urls;
    }

    /**
     * Debug des images (pour développement)
     */
    public function debug(Request $request, string $type): JsonResponse
    {
        if (!in_array($type, self::SUPPORTED_TYPES)) {
            return response()->json(['error' => 'Type non supporté'], 400);
        }

        $path = "images/{$type}";
        $files = [];
        
        if (Storage::disk('public')->exists($path)) {
            $allFiles = Storage::disk('public')->files($path);
            foreach ($allFiles as $file) {
                $files[] = [
                    'path' => $file,
                    'url' => Storage::disk('public')->url($file),
                    'size' => Storage::disk('public')->size($file),
                    'exists' => Storage::disk('public')->exists($file)
                ];
            }
        }

        return response()->json([
            'type' => $type,
            'path' => $path,
            'files' => $files,
            'storage_path' => storage_path('app/public/' . $path),
            'public_path' => public_path('storage/' . $path)
        ]);
    }

    /**
     * Servir une image (fallback pour les serveurs qui ne servent pas les fichiers statiques)
     */
    public function serve(Request $request, string $type, string $filename = null)
    {
        // Si filename est null, cela signifie que $type contient en fait le filename (pour les banners)
        if ($filename === null) {
            $path = "banners/{$type}"; // $type contient en fait le filename pour les banners
        } else {
            // Vérifier que le type est supporté
            if (!in_array($type, self::SUPPORTED_TYPES)) {
                abort(404);
            }

            // Construire le chemin du fichier
            $path = "images/{$type}/{$filename}";

            // Si le fichier n'existe pas, essayer avec l'extension .jpg
            if (!Storage::disk('public')->exists($path)) {
                $pathInfo = pathinfo($filename);
                $filenameWithoutExt = $pathInfo['filename'];
                $jpgPath = "images/{$type}/{$filenameWithoutExt}.jpg";

                if (Storage::disk('public')->exists($jpgPath)) {
                    $path = $jpgPath;
                } else {
                    abort(404);
                }
            }
        }

        // Vérifier que le fichier existe
        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        // Obtenir le contenu du fichier
        $file = Storage::disk('public')->get($path);
        $mimeType = Storage::disk('public')->mimeType($path);

        // Retourner la réponse avec les bons headers
        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=31536000') // Cache 1 an
            ->header('Expires', gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    }
}