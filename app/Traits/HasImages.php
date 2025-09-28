<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait HasImages
{
    /**
     * Obtenir l'URL de l'image selon la taille
     */
    public function getImageUrl(string $size = 'medium'): ?string
    {
        // Si c'est une URL externe
        if ($this->isExternalImageUrl()) {
            return $this->getImageUrlAttribute();
        }
        
        // Si c'est un fichier local
        if ($this->getImageFileAttribute()) {
            $imagePath = $this->getImagePath($size);
            if (Storage::disk('public')->exists($imagePath)) {
                return Storage::disk('public')->url($imagePath);
            }
        }
        
        return $this->getDefaultImageUrl($size);
    }

    /**
     * Obtenir le chemin de l'image selon la taille
     */
    public function getImagePath(string $size = 'medium'): string
    {
        $filename = $this->getImageFileAttribute();
        $type = $this->getImageType();
        
        if ($size === 'original') {
            return "images/{$type}/{$filename}";
        }
        
        return "images/{$type}/{$size}_{$filename}";
    }

    /**
     * Vérifier si l'image est une URL externe
     */
    public function isExternalImageUrl(): bool
    {
        $imageUrl = $this->getImageUrlAttribute();
        return $imageUrl && filter_var($imageUrl, FILTER_VALIDATE_URL) && !empty($imageUrl);
    }

    /**
     * Obtenir toutes les tailles d'images disponibles
     */
    public function getAllImageUrls(): array
    {
        if ($this->isExternalImageUrl()) {
            return [
                'original' => $this->getImageUrlAttribute(),
                'large' => $this->getImageUrlAttribute(),
                'medium' => $this->getImageUrlAttribute(),
                'thumbnail' => $this->getImageUrlAttribute(),
            ];
        }
        
        $urls = [];
        $sizes = $this->getAvailableImageSizes();
        
        foreach ($sizes as $size) {
            $urls[$size] = $this->getImageUrl($size);
        }
        
        return $urls;
    }

    /**
     * Supprimer toutes les images liées
     */
    public function deleteImages(): void
    {
        if (!$this->isExternalImageUrl() && $this->getImageFileAttribute()) {
            $type = $this->getImageType();
            $filename = $this->getImageFileAttribute();
            $uploadPath = "images/{$type}";
            
            // Supprimer toutes les tailles
            $sizes = $this->getAvailableImageSizes();
            foreach ($sizes as $size) {
                if ($size === 'original') {
                    $path = "{$uploadPath}/{$filename}";
                } else {
                    $path = "{$uploadPath}/{$size}_{$filename}";
                }
                
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }
    }

    /**
     * Mettre à jour l'image (upload ou URL)
     */
    public function updateImage($imageData): void
    {
        // Supprimer l'ancienne image si elle existe
        $this->deleteImages();
        
        if (isset($imageData['url']) && !empty($imageData['url'])) {
            // Image externe (URL)
            $this->setImageUrl($imageData['url']);
            $this->setImageFile(null);
        } elseif (isset($imageData['filename']) && !empty($imageData['filename'])) {
            // Image uploadée
            $this->setImageFile($imageData['filename']);
            $this->setImageUrl(null);
        }
        
        $this->save();
    }

    /**
     * Obtenir l'URL par défaut selon le type
     */
    protected function getDefaultImageUrl(string $size = 'medium'): string
    {
        $type = $this->getImageType();
        $defaults = [
            'events' => '/images/defaults/event-default.jpg',
            'venues' => '/images/defaults/venue-default.jpg',
            'users' => '/images/defaults/user-default.jpg',
            'organizers' => '/images/defaults/organizer-default.jpg',
        ];
        
        return $defaults[$type] ?? '/images/defaults/placeholder.jpg';
    }

    /**
     * Obtenir les tailles d'images disponibles selon le type
     */
    protected function getAvailableImageSizes(): array
    {
        $type = $this->getImageType();
        $sizes = [
            'events' => ['original', 'large', 'medium', 'thumbnail'],
            'venues' => ['original', 'medium', 'thumbnail'],
            'users' => ['original', 'medium', 'thumbnail'],
            'organizers' => ['original', 'medium', 'thumbnail'],
        ];
        
        return $sizes[$type] ?? ['original', 'medium', 'thumbnail'];
    }

    /**
     * Méthodes abstraites à implémenter dans le modèle
     */
    abstract protected function getImageType(): string;
    abstract protected function getImageUrlAttribute(): ?string;
    abstract protected function getImageFileAttribute(): ?string;
    abstract protected function setImageUrl(?string $url): void;
    abstract protected function setImageFile(?string $filename): void;
}